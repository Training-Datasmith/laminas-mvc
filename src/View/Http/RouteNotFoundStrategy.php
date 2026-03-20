<?php

declare (strict_types=1);
namespace Laminas\Mvc\View\Http;

use Exception;
use function is_string;
use Laminas\Event_Manager\Abstract_Listener_Aggregate;
use Laminas\Event_Manager\Event_Manager_Interface;
use Laminas\Http\Response as HttpResponse;
use Laminas\Mvc\Application;
use Laminas\Mvc\Mvc_Event;
use Laminas\Stdlib\Response_Interface as Response;
use Laminas\View\Model\View_Model;
use Throwable;
class Route_Not_Found_Strategy extends Abstract_Listener_Aggregate
{
    /**
     * Whether or not to display exceptions related to the 404 condition
     *
     * @var bool
     */
    protected $display_exceptions = false;
    /**
     * Whether or not to display the reason for a 404
     *
     * @var bool
     */
    protected $display_not_found_reason = false;
    /**
     * Template to use to report page not found conditions
     *
     * @var string
     */
    protected $not_found_template = 'error';
    /**
     * The reason for a not-found condition
     *
     * @var false|string
     */
    protected $reason = false;
    /**
     * {@inheritDoc}
     */
    public function attach(Event_Manager_Interface $events, $priority = 1): void
    {
        $this->listeners[] = $events->attach(Mvc_Event::EVENT_DISPATCH, $this->prepare_not_found_view_model(...), -90);
        $this->listeners[] = $events->attach(Mvc_Event::EVENT_DISPATCH_ERROR, $this->detect_not_found_error(...));
        $this->listeners[] = $events->attach(Mvc_Event::EVENT_DISPATCH_ERROR, $this->prepare_not_found_view_model(...));
    }
    /**
     * Set value indicating whether or not to display exceptions related to a not-found condition
     *
     * @param  bool $displayExceptions
     * @return RouteNotFoundStrategy
     */
    public function set_display_exceptions($display_exceptions)
    {
        $this->display_exceptions = (bool) $display_exceptions;
        return $this;
    }
    /**
     * Should we display exceptions related to a not-found condition?
     *
     * @return bool
     */
    public function display_exceptions()
    {
        return $this->display_exceptions;
    }
    /**
     * Set value indicating whether or not to display the reason for a not-found condition
     *
     * @param  bool $displayNotFoundReason
     * @return RouteNotFoundStrategy
     */
    public function set_display_not_found_reason($display_not_found_reason)
    {
        $this->display_not_found_reason = (bool) $display_not_found_reason;
        return $this;
    }
    /**
     * Should we display the reason for a not-found condition?
     *
     * @return bool
     */
    public function display_not_found_reason()
    {
        return $this->display_not_found_reason;
    }
    /**
     * Get template for not found conditions
     *
     * @param  string $notFoundTemplate
     * @return RouteNotFoundStrategy
     */
    public function set_not_found_template($not_found_template)
    {
        $this->not_found_template = (string) $not_found_template;
        return $this;
    }
    /**
     * Get template for not found conditions
     *
     * @return string
     */
    public function get_not_found_template()
    {
        return $this->not_found_template;
    }
    /**
     * Detect if an error is a 404 condition
     *
     * If a "controller not found" or "invalid controller" error type is
     * encountered, sets the response status code to 404.
     */
    public function detect_not_found_error(Mvc_Event $e): void
    {
        $error = $e->get_error();
        if (empty($error)) {
            return;
        }
        switch ($error) {
            case Application::ERROR_CONTROLLER_NOT_FOUND:
            case Application::ERROR_CONTROLLER_INVALID:
            case Application::ERROR_ROUTER_NO_MATCH:
                $this->reason = $error;
                $response = $e->get_response();
                if (!$response) {
                    $response = new Http_Response();
                    $e->set_response($response);
                }
                $response->set_status_code(404);
                break;
            default:
                return;
        }
    }
    /**
     * Create and return a 404 view model
     */
    public function prepare_not_found_view_model(Mvc_Event $e): void
    {
        $vars = $e->get_result();
        if ($vars instanceof Response) {
            // Already have a response as the result
            return;
        }
        $response = $e->get_response();
        if ($response->get_status_code() !== 404) {
            // Only handle 404 responses
            return;
        }
        if (!$vars instanceof View_Model) {
            $model = new View_Model();
            if (is_string($vars)) {
                $model->set_variable('message', $vars);
            } else {
                $model->set_variable('message', 'Page not found.');
            }
        } else {
            $model = $vars;
            if ($model->get_variable('message') === null) {
                $model->set_variable('message', 'Page not found.');
            }
        }
        $model->set_template($this->get_not_found_template());
        // If displaying reasons, inject the reason
        $this->inject_not_found_reason($model);
        // If displaying exceptions, inject
        $this->inject_exception($model, $e);
        // Inject controller if we're displaying either the reason or the exception
        $this->inject_controller($model, $e);
        $e->set_result($model);
    }
    /**
     * Inject the not-found reason into the model
     *
     * If $displayNotFoundReason is enabled, checks to see if $reason is set,
     * and, if so, injects it into the model. If not, it injects
     * Application::ERROR_CONTROLLER_CANNOT_DISPATCH.
     *
     * @return void
     */
    protected function inject_not_found_reason(View_Model $model)
    {
        if (!$this->display_not_found_reason()) {
            return;
        }
        // no route match, controller not found, or controller invalid
        if ($this->reason) {
            $model->set_variable('reason', $this->reason);
            return;
        }
        // otherwise, must be a case of the controller not being able to
        // dispatch itself.
        $model->set_variable('reason', Application::ERROR_CONTROLLER_CANNOT_DISPATCH);
    }
    /**
     * Inject the exception message into the model
     *
     * If $displayExceptions is enabled, and an exception is found in the
     * event, inject it into the model.
     *
     * @param  ViewModel $model
     * @param  MvcEvent $e
     * @return void
     */
    protected function inject_exception($model, $e)
    {
        if (!$this->display_exceptions()) {
            return;
        }
        $model->set_variable('display_exceptions', true);
        $exception = $e->get_param('exception', false);
        // @TODO clean up once PHP 7 requirement is enforced
        if (!$exception instanceof Exception && !$exception instanceof Throwable) {
            return;
        }
        $model->set_variable('exception', $exception);
    }
    /**
     * Inject the controller and controller class into the model
     *
     * If either $displayExceptions or $displayNotFoundReason are enabled,
     * injects the controllerClass from the MvcEvent. It checks to see if a
     * controller is present in the MvcEvent, and, if not, grabs it from
     * the route match if present; if a controller is found, it injects it into
     * the model.
     *
     * @param  ViewModel $model
     * @param  MvcEvent $e
     * @return void
     */
    protected function inject_controller($model, $e)
    {
        if (!$this->display_exceptions() && !$this->display_not_found_reason()) {
            return;
        }
        $controller = $e->get_controller();
        if (empty($controller)) {
            $route_match = $e->get_route_match();
            if (empty($route_match)) {
                return;
            }
            $controller = $route_match->get_param('controller', false);
            if (!$controller) {
                return;
            }
        }
        $controller_class = $e->get_controller_class();
        $model->set_variable('controller', $controller);
        $model->set_variable('controller_class', $controller_class);
    }
}