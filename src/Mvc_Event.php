<?php

declare (strict_types=1);
namespace Laminas\Mvc;

use Laminas\Event_Manager\Event;
use Laminas\Router\Route_Match;
use Laminas\Router\Route_Stack_Interface;
use Laminas\Stdlib\Request_Interface as Request;
use Laminas\Stdlib\Response_Interface as Response;
use Laminas\View\Model\Model_Interface as Model;
use Laminas\View\Model\View_Model;
/**
 * Shared event object propagated through the entire MVC request lifecycle.
 *
 * The MvcEvent is created during bootstrap() and passed by reference through every
 * lifecycle stage. Listeners read from and write to it to communicate state:
 *   - The router writes the RouteMatch after routing
 *   - The dispatcher writes the result after dispatching
 *   - Error listeners set the error message and controller class
 *
 * @since 3.0.0
 */
class Mvc_Event extends Event
{
    /**
     * Event name constants for the MVC lifecycle stages.
     *
     * Attach listeners using these constants to ensure typo-safety.
     */
    public const EVENT_BOOTSTRAP = 'bootstrap';
    public const EVENT_DISPATCH = 'dispatch';
    public const EVENT_DISPATCH_ERROR = 'dispatch.error';
    public const EVENT_FINISH = 'finish';
    public const EVENT_RENDER = 'render';
    public const EVENT_RENDER_ERROR = 'render.error';
    public const EVENT_ROUTE = 'route';
    /** @var ApplicationInterface|null */
    protected $application;
    /** @var Request */
    protected $request;
    /** @var Response */
    protected $response;
    /** @var mixed */
    protected $result;
    /** @var RouteStackInterface */
    protected $router;
    /** @var null|RouteMatch */
    protected $route_match;
    /** @var Model */
    protected $view_model;
    /**
     * Set the application instance on the event.
     *
     * @param Application_Interface $application The running application
     * @return static Fluent interface
     * @since 3.0.0
     */
    public function set_application(Application_Interface $application): static
    {
        $this->set_param('application', $application);
        $this->application = $application;
        return $this;
    }
    /**
     * Retrieve the application instance from the event.
     *
     * @return Application_Interface|null The running application, or null if not yet set
     * @since 3.0.0
     */
    public function get_application(): ?Application_Interface
    {
        return $this->application;
    }
    /**
     * Retrieve the router that resolved this request.
     *
     * @return Route_Stack_Interface|null The router, or null before routing
     * @since 3.0.0
     */
    public function get_router(): ?Route_Stack_Interface
    {
        return $this->router;
    }
    /**
     * Set the router on the event.
     *
     * @param Route_Stack_Interface $router The router to attach
     * @return static Fluent interface
     * @since 3.0.0
     */
    public function set_router(Route_Stack_Interface $router): static
    {
        $this->set_param('router', $router);
        $this->router = $router;
        return $this;
    }
    /**
     * Retrieve the route match produced by routing.
     *
     * @return Route_Match|null The matched route, or null if routing has not run or failed
     * @since 3.0.0
     */
    public function get_route_match(): ?Route_Match
    {
        return $this->route_match;
    }
    /**
     * Set the route match on the event.
     *
     * Called by RouteListener after the router successfully matches a request.
     *
     * @param Route_Match $matches The matched route containing matched params
     * @return static Fluent interface
     * @since 3.0.0
     */
    public function set_route_match(Route_Match $matches): static
    {
        $this->set_param('route-match', $matches);
        $this->route_match = $matches;
        return $this;
    }
    /**
     * Retrieve the request from the event.
     *
     * @return Request The HTTP (or CLI) request for this dispatch cycle
     * @since 3.0.0
     */
    public function get_request(): Request
    {
        return $this->request;
    }
    /**
     * Set the request on the event.
     *
     * @param Request $request The request for this dispatch cycle
     * @return static Fluent interface
     * @since 3.0.0
     */
    public function set_request(Request $request): static
    {
        $this->set_param('request', $request);
        $this->request = $request;
        return $this;
    }
    /**
     * Retrieve the response from the event.
     *
     * @return Response The mutable response for this dispatch cycle
     * @since 3.0.0
     */
    public function get_response(): Response
    {
        return $this->response;
    }
    /**
     * Set the response on the event.
     *
     * @param Response $response The response to attach
     * @return static Fluent interface
     * @since 3.0.0
     */
    public function set_response(Response $response): static
    {
        $this->set_param('response', $response);
        $this->response = $response;
        return $this;
    }
    /**
     * Set the view model on the event.
     *
     * @param Model $view_model The view model to render for this dispatch cycle
     * @return static Fluent interface
     * @since 3.0.0
     */
    public function set_view_model(Model $view_model): static
    {
        $this->view_model = $view_model;
        return $this;
    }
    /**
     * Retrieve the view model, lazy-creating a plain ViewModel if none has been set.
     *
     * @return Model The view model for this dispatch cycle
     * @since 3.0.0
     */
    public function get_view_model(): Model
    {
        if (null === $this->view_model) {
            $this->set_view_model(new View_Model());
        }
        return $this->view_model;
    }
    /**
     * Retrieve the dispatch result, if any.
     *
     * This is the value returned by the controller (typically a ViewModel or Response).
     *
     * @return mixed The dispatch result, or null if dispatch has not completed
     * @since 3.0.0
     */
    public function get_result(): mixed
    {
        return $this->result;
    }
    /**
     * Set the dispatch result on the event.
     *
     * @param mixed $result The value returned by the controller action
     * @return static Fluent interface
     * @since 3.0.0
     */
    public function set_result(mixed $result): static
    {
        $this->set_param('__RESULT__', $result);
        $this->result = $result;
        return $this;
    }
    /**
     * Determine whether this event represents an error state.
     *
     * Returns true if an error message has been set on the event, indicating
     * that routing or dispatch encountered a problem (e.g. controller not found).
     *
     * @return bool True if an error has been recorded on this event
     * @see set_error()
     * @since 3.0.0
     */
    public function is_error(): bool
    {
        return (bool) $this->get_param('error', false);
    }
    /**
     * Set an error message on the event to signal a dispatch error.
     *
     * Use one of the Application::ERROR_* constants as the message value for
     * consistency. Error-handling listeners inspect this value to select the
     * appropriate error view or response.
     *
     * @param string $message One of the Application::ERROR_* constant values
     * @return static Fluent interface
     * @see Application::ERROR_CONTROLLER_NOT_FOUND
     * @see is_error()
     * @since 3.0.0
     */
    public function set_error(string $message): static
    {
        $this->set_param('error', $message);
        return $this;
    }
    /**
     * Retrieve the error message set on the event.
     *
     * @return string The error message, or an empty string if no error is set
     * @see set_error()
     * @since 3.0.0
     */
    public function get_error(): string
    {
        return $this->get_param('error', '');
    }
    /**
     * Get the name of the controller resolved for this request.
     *
     * @return string|null The controller service name, or null if not yet resolved
     * @since 3.0.0
     */
    public function get_controller(): ?string
    {
        return $this->get_param('controller');
    }
    /**
     * Set the controller service name on the event.
     *
     * @param string $name The service name of the matched controller
     * @return static Fluent interface
     * @since 3.0.0
     */
    public function set_controller(string $name): static
    {
        $this->set_param('controller', $name);
        return $this;
    }
    /**
     * Get the fully-qualified class name of the resolved controller.
     *
     * @return string|null The FQCN of the controller, or null if not yet resolved
     * @since 3.0.0
     */
    public function get_controller_class(): ?string
    {
        return $this->get_param('controller-class');
    }
    /**
     * Set the controller class name on the event.
     *
     * @param string $class The fully-qualified class name of the dispatched controller
     * @return static Fluent interface
     * @since 3.0.0
     */
    public function set_controller_class(string $class): static
    {
        $this->set_param('controller-class', $class);
        return $this;
    }
}