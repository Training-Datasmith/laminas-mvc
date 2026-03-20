<?php

declare (strict_types=1);
namespace Laminas\Mvc\Service;

// phpcs:ignore
use Interop\Container\Container_Interface;
use Laminas\Module_Manager\Feature\Controller_Plugin_Provider_Interface;
use Laminas\Module_Manager\Feature\Controller_Provider_Interface;
use Laminas\Module_Manager\Feature\Route_Provider_Interface;
use Laminas\Module_Manager\Feature\Service_Provider_Interface;
use Laminas\Module_Manager\Feature\View_Helper_Provider_Interface;
use Laminas\Module_Manager\Listener\Default_Listener_Aggregate;
use Laminas\Module_Manager\Listener\Listener_Options;
use Laminas\Module_Manager\Module_Event;
use Laminas\Module_Manager\Module_Manager;
use Laminas\Service_Manager\Factory\Factory_Interface;
class Module_Manager_Factory implements Factory_Interface
{
    /**
     * Creates and returns the module manager
     *
     * Instantiates the default module listeners, providing them configuration
     * from the "module_listener_options" key of the ApplicationConfig
     * service. Also sets the default config glob path.
     *
     * Module manager is instantiated and provided with an EventManager, to which
     * the default listener aggregate is attached. The ModuleEvent is also created
     * and attached to the module manager.
     *
     * @param  string $requestedName
     */
    public function __invoke(Container_Interface $container, $requested_name, ?array $options = null): \Laminas\Module_Manager\Module_Manager
    {
        $configuration = $container->get('ApplicationConfig');
        $listener_options = new Listener_Options($configuration['module_listener_options']);
        $default_listeners = new Default_Listener_Aggregate($listener_options);
        $service_listener = $container->get('ServiceListener');
        $service_listener->add_service_manager($container, 'service_manager', Service_Provider_Interface::class, 'getServiceConfig');
        $service_listener->add_service_manager('ControllerManager', 'controllers', Controller_Provider_Interface::class, 'getControllerConfig');
        $service_listener->add_service_manager('ControllerPluginManager', 'controller_plugins', Controller_Plugin_Provider_Interface::class, 'getControllerPluginConfig');
        $service_listener->add_service_manager('ViewHelperManager', 'view_helpers', View_Helper_Provider_Interface::class, 'getViewHelperConfig');
        $service_listener->add_service_manager('RoutePluginManager', 'route_manager', Route_Provider_Interface::class, 'getRouteConfig');
        $events = $container->get('EventManager');
        $default_listeners->attach($events);
        $service_listener->attach($events);
        $module_event = new Module_Event();
        $module_event->set_param('ServiceManager', $container);
        $module_manager = new Module_Manager($configuration['modules'], $events);
        $module_manager->set_event($module_event);
        return $module_manager;
    }
}