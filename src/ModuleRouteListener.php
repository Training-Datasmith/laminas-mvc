<?php

declare (strict_types=1);
namespace Laminas\Mvc;

use Laminas\Event_Manager\Abstract_Listener_Aggregate;
use Laminas\Event_Manager\Event_Manager_Interface;
use Laminas\Router\Route_Match;
use function str_replace;
use function str_starts_with;
use function ucwords;
class Module_Route_Listener extends Abstract_Listener_Aggregate
{
    public const MODULE_NAMESPACE = '__NAMESPACE__';
    public const ORIGINAL_CONTROLLER = '__CONTROLLER__';
    /**
     * Attach to an event manager
     *
     * @param  int $priority
     */
    public function attach(Event_Manager_Interface $events, $priority = 1): void
    {
        $this->listeners[] = $events->attach(Mvc_Event::EVENT_ROUTE, $this->on_route(...), $priority);
    }
    /**
     * Listen to the "route" event and determine if the module namespace should
     * be prepended to the controller name.
     *
     * If the route match contains a parameter key matching the MODULE_NAMESPACE
     * constant, that value will be prepended, with a namespace separator, to
     * the matched controller parameter.
     */
    public function on_route(Mvc_Event $e): void
    {
        $matches = $e->get_route_match();
        if (!$matches instanceof Route_Match) {
            // Can't do anything without a route match
            return;
        }
        $module = $matches->get_param(self::MODULE_NAMESPACE, false);
        if (!$module) {
            // No module namespace found; nothing to do
            return;
        }
        $controller = $matches->get_param('controller', false);
        if (!$controller) {
            // no controller matched, nothing to do
            return;
        }
        // Ensure the module namespace has not already been applied
        if (str_starts_with($controller, $module)) {
            return;
        }
        // Keep the originally matched controller name around
        $matches->set_param(self::ORIGINAL_CONTROLLER, $controller);
        // Prepend the controllername with the module, and replace it in the
        // matches
        $controller = $module . '\\' . str_replace(' ', '', ucwords(str_replace('-', ' ', $controller)));
        $matches->set_param('controller', $controller);
    }
}