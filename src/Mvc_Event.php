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
class Mvc_Event extends Event
{
    /**#@+
     * Mvc events triggered by eventmanager
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
     * Set application instance
     *
     * @return MvcEvent
     */
    public function set_application(Application_Interface $application)
    {
        $this->set_param('application', $application);
        $this->application = $application;
        return $this;
    }
    /**
     * Get application instance
     *
     * @return ApplicationInterface
     */
    public function get_application()
    {
        return $this->application;
    }
    /**
     * Get router
     *
     * @return RouteStackInterface
     */
    public function get_router()
    {
        return $this->router;
    }
    /**
     * Set router
     *
     * @return MvcEvent
     */
    public function set_router(Route_Stack_Interface $router)
    {
        $this->set_param('router', $router);
        $this->router = $router;
        return $this;
    }
    /**
     * Get route match
     *
     * @return null|RouteMatch
     */
    public function get_route_match()
    {
        return $this->route_match;
    }
    /**
     * Set route match
     *
     * @return MvcEvent
     */
    public function set_route_match(Route_Match $matches)
    {
        $this->set_param('route-match', $matches);
        $this->route_match = $matches;
        return $this;
    }
    /**
     * Get request
     *
     * @return Request
     */
    public function get_request()
    {
        return $this->request;
    }
    /**
     * Set request
     *
     * @return MvcEvent
     */
    public function set_request(Request $request)
    {
        $this->set_param('request', $request);
        $this->request = $request;
        return $this;
    }
    /**
     * Get response
     *
     * @return Response
     */
    public function get_response()
    {
        return $this->response;
    }
    /**
     * Set response
     *
     * @return MvcEvent
     */
    public function set_response(Response $response)
    {
        $this->set_param('response', $response);
        $this->response = $response;
        return $this;
    }
    /**
     * Set the view model
     *
     * @return MvcEvent
     */
    public function set_view_model(Model $view_model)
    {
        $this->view_model = $view_model;
        return $this;
    }
    /**
     * Get the view model
     *
     * @return Model
     */
    public function get_view_model()
    {
        if (null === $this->view_model) {
            $this->set_view_model(new View_Model());
        }
        return $this->view_model;
    }
    /**
     * Get result
     *
     * @return mixed
     */
    public function get_result()
    {
        return $this->result;
    }
    /**
     * Set result
     *
     * @return MvcEvent
     */
    public function set_result(mixed $result)
    {
        $this->set_param('__RESULT__', $result);
        $this->result = $result;
        return $this;
    }
    /**
     * Does the event represent an error response?
     *
     * @return bool
     */
    public function is_error()
    {
        return (bool) $this->get_param('error', false);
    }
    /**
     * Set the error message (indicating error in handling request)
     *
     * @param  string $message
     * @return MvcEvent
     */
    public function set_error($message)
    {
        $this->set_param('error', $message);
        return $this;
    }
    /**
     * Retrieve the error message, if any
     *
     * @return string
     */
    public function get_error()
    {
        return $this->get_param('error', '');
    }
    /**
     * Get the currently registered controller name
     *
     * @return string
     */
    public function get_controller()
    {
        return $this->get_param('controller');
    }
    /**
     * Set controller name
     *
     * @param  string $name
     * @return MvcEvent
     */
    public function set_controller($name)
    {
        $this->set_param('controller', $name);
        return $this;
    }
    /**
     * Get controller class
     *
     * @return string
     */
    public function get_controller_class()
    {
        return $this->get_param('controller-class');
    }
    /**
     * Set controller class
     *
     * @param string $class
     * @return MvcEvent
     */
    public function set_controller_class($class)
    {
        $this->set_param('controller-class', $class);
        return $this;
    }
}