<?php

declare (strict_types=1);
namespace Laminas\Mvc;

use ArrayObject;
use Exception;
use function function_exists;
use function is_object;
use Laminas\Event_Manager\Abstract_Listener_Aggregate;
use Laminas\Event_Manager\Event_Manager_Interface;
use Laminas\Mvc\Controller\Controller_Manager;
use Laminas\Mvc\Exception\Invalid_Controller_Exception;
use Laminas\Router\Route_Match;
use Laminas\Service_Manager\Exception\Invalid_Service_Exception;
use Laminas\Stdlib\Array_Utils;
use Throwable;
/**
 * Default dispatch listener
 *
 * Pulls controllers from the service manager's "ControllerManager" service.
 *
 * If the controller cannot be found a "404" result is set up. Otherwise it
 * will continue to try to load the controller.
 *
 * If the controller is not dispatchable it sets up a "404" result. In case
 * of any other exceptions it trigger the "dispatch.error" event in an attempt
 * to return a 500 status.
 *
 * If the controller subscribes to InjectApplicationEventInterface, it injects
 * the current MvcEvent into the controller.
 *
 * It then calls the controller's "dispatch" method, passing it the request and
 * response. If an exception occurs, it triggers the "dispatch.error" event,
 * in an attempt to return a 500 status.
 *
 * The return value of dispatching the controller is placed into the result
 * property of the MvcEvent, and returned.
 */
class Dispatch_Listener extends Abstract_Listener_Aggregate
{
    public function __construct(private readonly Controller_Manager $controller_manager)
    {
    }
    /**
     * Attach listeners to an event manager
     *
     * @param  int $priority
     */
    public function attach(Event_Manager_Interface $events, $priority = 1): void
    {
        $this->listeners[] = $events->attach(Mvc_Event::EVENT_DISPATCH, $this->on_dispatch(...));
        if (function_exists('zend_monitor_custom_event_ex')) {
            $this->listeners[] = $events->attach(Mvc_Event::EVENT_DISPATCH_ERROR, $this->report_monitor_event(...));
        }
    }
    /**
     * Listen to the "dispatch" event
     *
     * @return mixed
     */
    public function on_dispatch(Mvc_Event $e)
    {
        if (null !== $e->get_result()) {
            return;
        }
        $route_match = $e->get_route_match();
        $controller_name = $route_match instanceof Route_Match ? $route_match->get_param('controller', 'not-found') : 'not-found';
        $application = $e->get_application();
        $controller_manager = $this->controller_manager;
        // Query abstract controllers, too!
        if (!$controller_manager->has($controller_name)) {
            $return = $this->marshal_controller_not_found_event($application::ERROR_CONTROLLER_NOT_FOUND, $controller_name, $e, $application);
            return $this->complete($return, $e);
        }
        try {
            $controller = $controller_manager->get($controller_name);
        } catch (Invalid_Controller_Exception|Invalid_Service_Exception $exception) {
            $return = $this->marshal_controller_not_found_event($application::ERROR_CONTROLLER_INVALID, $controller_name, $e, $application, $exception);
            return $this->complete($return, $e);
        } catch (Throwable $exception) {
            $return = $this->marshal_bad_controller_event($controller_name, $e, $application, $exception);
            return $this->complete($return, $e);
        }
        if ($controller instanceof Inject_Application_Event_Interface) {
            $controller->set_event($e);
        }
        $request = $e->get_request();
        $response = $application->get_response();
        $caught_exception = null;
        try {
            $return = $controller->dispatch($request, $response);
        } catch (Throwable $ex) {
            $caught_exception = $ex;
        }
        if ($caught_exception !== null) {
            $e->set_name(Mvc_Event::EVENT_DISPATCH_ERROR);
            $e->set_error($application::ERROR_EXCEPTION);
            $e->set_controller($controller_name);
            $e->set_controller_class($controller::class);
            $e->set_param('exception', $caught_exception);
            $return = $application->get_event_manager()->trigger_event($e)->last();
            if (!$return) {
                $return = $e->get_result();
            }
        }
        return $this->complete($return, $e);
    }
    public function report_monitor_event(Mvc_Event $e): void
    {
        $error = $e->get_error();
        $exception = $e->get_param('exception');
        // @TODO clean up once PHP 7 requirement is enforced
        if ($exception instanceof Exception || $exception instanceof Throwable) {
            zend_monitor_custom_event_ex($error, $exception->get_message(), 'Laminas Exception', ['code' => $exception->get_code(), 'trace' => $exception->get_trace_as_string()]);
        }
    }
    /**
     * Complete the dispatch
     *
     * @return mixed
     */
    protected function complete(mixed $return, Mvc_Event $event)
    {
        if (!is_object($return)) {
            if (Array_Utils::has_string_keys($return)) {
                $return = new ArrayObject($return, ArrayObject::ARRAY_AS_PROPS);
            }
        }
        $event->set_result($return);
        return $return;
    }
    /**
     * Marshal a controller not found exception event
     *
     * @param  string $type
     * @param  string $controllerName
     * @param Throwable|Exception $exception
     * @return mixed
     */
    protected function marshal_controller_not_found_event($type, $controller_name, Mvc_Event $event, Application $application, $exception = null)
    {
        $event->set_name(Mvc_Event::EVENT_DISPATCH_ERROR);
        $event->set_error($type);
        $event->set_controller($controller_name);
        $event->set_controller_class('invalid controller class or alias: ' . $controller_name);
        if ($exception !== null) {
            $event->set_param('exception', $exception);
        }
        $events = $application->get_event_manager();
        $results = $events->trigger_event($event);
        $return = $results->last();
        if (!$return) {
            return $event->get_result();
        }
        return $return;
    }
    /**
     * Marshal a bad controller exception event
     *
     * @param  string $controllerName
     * @param Throwable|Exception $exception
     * @return mixed
     */
    protected function marshal_bad_controller_event($controller_name, Mvc_Event $event, Application $application, $exception)
    {
        $event->set_name(Mvc_Event::EVENT_DISPATCH_ERROR);
        $event->set_error($application::ERROR_EXCEPTION);
        $event->set_controller($controller_name);
        $event->set_param('exception', $exception);
        $events = $application->get_event_manager();
        $results = $events->trigger_event($event);
        $return = $results->last();
        if (!$return) {
            return $event->get_result();
        }
        return $return;
    }
}