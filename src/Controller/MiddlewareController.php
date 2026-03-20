<?php

declare (strict_types=1);
namespace Laminas\Mvc\Controller;

use Laminas\Diactoros\Server_Request;
use Laminas\Event_Manager\Event_Manager_Interface;
use Laminas\Http\Request;
use Laminas\Mvc\Exception\Reached_Final_Handler_Exception;
use Laminas\Mvc\Exception\RuntimeException;
use Laminas\Mvc\Mvc_Event;
use Laminas\Psr7Bridge\Psr7server_Request;
use Laminas\Router\Route_Match;
use Laminas\Stratigility\Delegate\Callable_Delegate_Decorator;
use Laminas\Stratigility\Middleware_Pipe;
use Psr\Http\Message\Response_Interface;
use Psr\Http\Message\Server_Request_Interface;
use function sprintf;
/**
 * @internal don't use this in your codebase, or else \@ocramius will hunt you
 *     down. This is just an internal hack to make middleware trigger
 *     'dispatch' events attached to the DispatchableInterface identifier.
 *
 *     Specifically, it will receive a \@see MiddlewarePipe and a
 *     \@see ResponseInterface prototype, and then dispatch the pipe whilst still
 *     behaving like a normal controller. That is needed for any events
 *     attached to the \@see \Laminas\Stdlib\DispatchableInterface identifier to
 *     reach their listeners on any attached \@see \Laminas\EventManager\SharedEventManagerInterface
 */
final class Middleware_Controller extends Abstract_Controller
{
    public function __construct(private readonly Middleware_Pipe $pipe, private readonly Response_Interface $response_prototype, Event_Manager_Interface $event_manager, Mvc_Event $event)
    {
        $this->event_identifier = self::class;
        $this->set_event_manager($event_manager);
        $this->set_event($event);
    }
    /**
     * {@inheritDoc}
     *
     * @throws RuntimeException
     */
    public function on_dispatch(Mvc_Event $e)
    {
        $route_match = $e->get_route_match();
        $psr7Request = $this->populate_request_parameters_from_route($this->load_request()->with_attribute(Route_Match::class, $route_match), $route_match);
        $result = $this->pipe->process($psr7Request, new Callable_Delegate_Decorator(static function (): never {
            throw Reached_Final_Handler_Exception::create();
        }, $this->response_prototype));
        $e->set_result($result);
        return $result;
    }
    /**
     * @return ServerRequest
     * @throws RuntimeException
     */
    private function load_request()
    {
        $request = $this->request;
        if (!$request instanceof Request) {
            throw new RuntimeException(sprintf('Expected request to be a %s, %s given', Request::class, $request::class));
        }
        return Psr7server_Request::from_laminas($request);
    }
    /**
     * @return ServerRequestInterface
     */
    private function populate_request_parameters_from_route(Server_Request_Interface $request, ?Route_Match $route_match = null)
    {
        if (!$route_match) {
            return $request;
        }
        foreach ($route_match->get_params() as $key => $value) {
            $request = $request->with_attribute($key, $value);
        }
        return $request;
    }
}