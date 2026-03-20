<?php

declare (strict_types=1);
namespace Laminas\Mvc;

use Laminas\Event_Manager\Abstract_Listener_Aggregate;
use Laminas\Event_Manager\Event_Manager_Interface;
use Laminas\Router\Route_Match;
class Route_Listener extends Abstract_Listener_Aggregate
{
    /**
     * Attach to an event manager
     *
     * @param  int $priority
     */
    public function attach(Event_Manager_Interface $events, $priority = 1): void
    {
        $this->listeners[] = $events->attach(Mvc_Event::EVENT_ROUTE, $this->on_route(...));
    }
    /**
     * Listen to the "route" event and attempt to route the request
     *
     * If no matches are returned, triggers "dispatch.error" in order to
     * create a 404 response.
     *
     * Seeds the event with the route match on completion.
     *
     * @return null|RouteMatch
     */
    public function on_route(Mvc_Event $event)
    {
        $request = $event->get_request();
        $router = $event->get_router();
        $route_match = $router->match($request);
        if ($route_match instanceof Route_Match) {
            $event->set_route_match($route_match);
            return $route_match;
        }
        $event->set_name(Mvc_Event::EVENT_DISPATCH_ERROR);
        $event->set_error(Application::ERROR_ROUTER_NO_MATCH);
        $target = $event->get_target();
        $results = $target->get_event_manager()->trigger_event($event);
        if (!empty($results)) {
            return $results->last();
        }
        return $event->get_params();
    }
}