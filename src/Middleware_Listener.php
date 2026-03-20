<?php

declare (strict_types=1);
namespace Laminas\Mvc;

use const E_USER_DEPRECATED;
// phpcs:ignore
use Exception;
use Interop\Container\Container_Interface;
use Interop\Http\Server_Middleware\Middleware_Interface;
use function is_array;
use function is_callable;
use function is_string;
use Laminas\Event_Manager\Abstract_Listener_Aggregate;
use Laminas\Event_Manager\Event_Manager_Interface;
use Laminas\Mvc\Controller\Middleware_Controller;
use Laminas\Mvc\Exception\Invalid_Middleware_Exception;
use Laminas\Psr7Bridge\Psr7Response;
use Laminas\Stratigility\Middleware_Pipe;
use Psr\Http\Message\Response_Interface;
use Psr\Http\Message\Response_Interface as PsrResponseInterface;
use function sprintf;
use Throwable;
use function trigger_error;
/**
 * @deprecated Since 3.2.0
 */
class Middleware_Listener extends Abstract_Listener_Aggregate
{
    /**
     * Attach listeners to an event manager
     *
     * @param  int                   $priority
     */
    public function attach(Event_Manager_Interface $events, $priority = 1): void
    {
        $this->listeners[] = $events->attach(Mvc_Event::EVENT_DISPATCH, $this->on_dispatch(...), 1);
    }
    /**
     * Listen to the "dispatch" event
     *
     * @return mixed
     */
    public function on_dispatch(Mvc_Event $event)
    {
        if (null !== $event->get_result()) {
            return;
        }
        $route_match = $event->get_route_match();
        $middleware = $route_match->get_param('middleware', false);
        if (false === $middleware) {
            return;
        }
        trigger_error(sprintf('Dispatching middleware with %s is deprecated since 3.2.0;' . ' please use the laminas/laminas-mvc-middleware package instead', self::class), E_USER_DEPRECATED);
        $request = $event->get_request();
        $application = $event->get_application();
        $response = $application->get_response();
        $service_manager = $application->get_service_manager();
        $psr7response_prototype = Psr7Response::from_laminas($response);
        try {
            $pipe = $this->create_pipe_from_spec($service_manager, $psr7response_prototype, is_array($middleware) ? $middleware : [$middleware]);
        } catch (Invalid_Middleware_Exception $invalid_middleware_exception) {
            $return = $this->marshal_invalid_middleware($application::ERROR_MIDDLEWARE_CANNOT_DISPATCH, $invalid_middleware_exception->to_middleware_name(), $event, $application, $invalid_middleware_exception);
            $event->set_result($return);
            return $return;
        }
        $caught_exception = null;
        try {
            $return = (new Middleware_Controller($pipe, $psr7response_prototype, $application->get_service_manager()->get('EventManager'), $event))->dispatch($request, $response);
        } catch (Throwable $ex) {
            $caught_exception = $ex;
        }
        if ($caught_exception !== null) {
            $event->set_name(Mvc_Event::EVENT_DISPATCH_ERROR);
            $event->set_error($application::ERROR_EXCEPTION);
            $event->set_param('exception', $caught_exception);
            $events = $application->get_event_manager();
            $results = $events->trigger_event($event);
            $return = $results->last();
            if (!$return) {
                $return = $event->get_result();
            }
        } else {
            $event->set_error('');
        }
        if (!$return instanceof Psr_Response_Interface) {
            $event->set_result($return);
            return $return;
        }
        $response = Psr7Response::to_laminas($return);
        $event->set_result($response);
        return $response;
    }
    /**
     * Create a middleware pipe from the array spec given.
     *
     * @throws InvalidMiddlewareException
     */
    private function create_pipe_from_spec(Container_Interface $service_locator, Response_Interface $response_prototype, array $middlewares_to_be_piped): \Laminas\Stratigility\Middleware_Pipe
    {
        $pipe = new Middleware_Pipe();
        $pipe->set_response_prototype($response_prototype);
        foreach ($middlewares_to_be_piped as $middleware_to_be_piped) {
            if (null === $middleware_to_be_piped) {
                throw Invalid_Middleware_Exception::from_null();
            }
            $middleware_name = is_string($middleware_to_be_piped) ? $middleware_to_be_piped : $middleware_to_be_piped::class;
            if (is_string($middleware_to_be_piped) && $service_locator->has($middleware_to_be_piped)) {
                $middleware_to_be_piped = $service_locator->get($middleware_to_be_piped);
            }
            if (!$middleware_to_be_piped instanceof Middleware_Interface && !is_callable($middleware_to_be_piped)) {
                throw Invalid_Middleware_Exception::from_middleware_name($middleware_name);
            }
            $pipe->pipe($middleware_to_be_piped);
        }
        return $pipe;
    }
    /**
     * Marshal a middleware not callable exception event
     *
     * @param  string $type
     * @param  string $middlewareName
     * @return mixed
     */
    protected function marshal_invalid_middleware($type, $middleware_name, Mvc_Event $event, Application $application, ?Exception $exception = null)
    {
        $event->set_name(Mvc_Event::EVENT_DISPATCH_ERROR);
        $event->set_error($type);
        $event->set_controller($middleware_name);
        $event->set_controller_class('Middleware not callable: ' . $middleware_name);
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
}