<?php

declare (strict_types=1);
namespace Laminas\Mvc\Controller\Plugin;

use function array_merge;
use function func_num_args;
use function is_array;
use function is_bool;
use function iterator_to_array;
use Laminas\Event_Manager\Event_Interface;
use Laminas\Mvc\Exception\DomainException;
use Laminas\Mvc\Exception\InvalidArgumentException;
use Laminas\Mvc\Exception\RuntimeException;
use Laminas\Mvc\Inject_Application_Event_Interface;
use Laminas\Mvc\Module_Route_Listener;
use Laminas\Mvc\Mvc_Event;
use Laminas\Router\Route_Stack_Interface;
use Traversable;
class Url extends Abstract_Plugin
{
    /**
     * Generates a URL based on a route
     *
     * @param  string             $route              RouteInterface name
     * @param  array|Traversable  $params             Parameters to use in url generation, if any
     * @param  array|bool         $options            RouteInterface-specific options to use in url generation, if any.
     *                                                If boolean, and no fourth argument, used as $reuseMatchedParams.
     * @param  bool               $reuseMatchedParams Whether to reuse matched parameters
     * @throws RuntimeException
     * @throws InvalidArgumentException
     * @throws DomainException
     * @return string
     */
    public function from_route($route = null, $params = [], $options = [], $reuse_matched_params = false)
    {
        $controller = $this->get_controller();
        if (!$controller instanceof Inject_Application_Event_Interface) {
            throw new DomainException('Url plugin requires a controller that implements InjectApplicationEventInterface');
        }
        if (!is_array($params)) {
            if (!$params instanceof Traversable) {
                throw new InvalidArgumentException('Params is expected to be an array or a Traversable object');
            }
            $params = iterator_to_array($params);
        }
        $event = $controller->get_event();
        $router = null;
        $matches = null;
        if ($event instanceof Mvc_Event) {
            $router = $event->get_router();
            $matches = $event->get_route_match();
        } elseif ($event instanceof Event_Interface) {
            $router = $event->get_param('router', false);
            $matches = $event->get_param('route-match', false);
        }
        if (!$router instanceof Route_Stack_Interface) {
            throw new DomainException('Url plugin requires that controller event compose a router; none found');
        }
        if (3 === func_num_args() && is_bool($options)) {
            $reuse_matched_params = $options;
            $options = [];
        }
        if ($route === null) {
            if (!$matches) {
                throw new RuntimeException('No RouteMatch instance present');
            }
            $route = $matches->get_matched_route_name();
            if ($route === null) {
                throw new RuntimeException('RouteMatch does not contain a matched route name');
            }
        }
        if ($reuse_matched_params && $matches) {
            $route_match_params = $matches->get_params();
            if (isset($route_match_params[Module_Route_Listener::ORIGINAL_CONTROLLER])) {
                $route_match_params['controller'] = $route_match_params[Module_Route_Listener::ORIGINAL_CONTROLLER];
                unset($route_match_params[Module_Route_Listener::ORIGINAL_CONTROLLER]);
            }
            if (isset($route_match_params[Module_Route_Listener::MODULE_NAMESPACE])) {
                unset($route_match_params[Module_Route_Listener::MODULE_NAMESPACE]);
            }
            $params = array_merge($route_match_params, $params);
        }
        $options['name'] = $route;
        return $router->assemble($params, $options);
    }
}