<?php

declare (strict_types=1);
namespace Laminas\Mvc\Service;

// phpcs:ignore
use Interop\Container\Container_Interface;
use Laminas\Event_Manager\Event_Manager;
use Laminas\Event_Manager\Event_Manager_Aware_Interface;
use Laminas\Event_Manager\Event_Manager_Interface;
use Laminas\Event_Manager\Shared_Event_Manager;
use Laminas\Event_Manager\Shared_Event_Manager_Interface;
use Laminas\Module_Manager\Listener\Service_Listener;
use Laminas\Module_Manager\Module_Manager;
use Laminas\Service_Manager\Config;
use Laminas\Service_Manager\Service_Manager;
use Laminas\Stdlib\Array_Utils;
class Service_Manager_Config extends Config
{
    /**
     * Default service configuration.
     *
     * In addition to these, the constructor registers several factories and
     * initializers; see that method for details.
     *
     * @var array
     */
    protected $config = ['abstract_factories' => [], 'aliases' => ['EventManagerInterface' => Event_Manager::class, Event_Manager_Interface::class => 'EventManager', Module_Manager::class => 'ModuleManager', Service_Listener::class => 'ServiceListener', Shared_Event_Manager::class => 'SharedEventManager', 'SharedEventManagerInterface' => 'SharedEventManager', Shared_Event_Manager_Interface::class => 'SharedEventManager'], 'delegators' => [], 'factories' => ['EventManager' => Event_Manager_Factory::class, 'ModuleManager' => Module_Manager_Factory::class, 'ServiceListener' => Service_Listener_Factory::class], 'lazy_services' => [], 'initializers' => [], 'invokables' => [], 'services' => [], 'shared' => ['EventManager' => false]];
    /**
     * Constructor
     *
     * Merges internal arrays with those passed via configuration, and also
     * defines:
     *
     * - factory for the service 'SharedEventManager'.
     * - initializer for EventManagerAwareInterface implementations
     */
    public function __construct(array $config = [])
    {
        $this->config['factories']['ServiceManager'] = static fn($container) => $container;
        $this->config['factories']['SharedEventManager'] = static fn(): Shared_Event_Manager => new Shared_Event_Manager();
        $this->config['initializers'] = Array_Utils::merge($this->config['initializers'], ['EventManagerAwareInitializer' => static function ($first, $second): void {
            if ($first instanceof Container_Interface) {
                $container = $first;
                $instance = $second;
            } else {
                $container = $second;
                $instance = $first;
            }
            if (!$instance instanceof Event_Manager_Aware_Interface) {
                return;
            }
            $event_manager = $instance->get_event_manager();
            // If the instance has an EM WITH an SEM composed, do nothing.
            if ($event_manager instanceof Event_Manager_Interface && $event_manager->get_shared_manager() instanceof Shared_Event_Manager_Interface) {
                return;
            }
            $instance->set_event_manager($container->get('EventManager'));
        }]);
        parent::__construct($config);
    }
    /**
     * Configure service container.
     *
     * Uses the configuration present in the instance to configure the provided
     * service container.
     *
     * Before doing so, it adds a "service" entry for the ServiceManager class,
     * pointing to the provided service container.
     *
     * @return ServiceManager
     */
    public function configure_service_manager(Service_Manager $services)
    {
        $this->config['services'][Service_Manager::class] = $services;
        // This is invoked as part of the bootstrapping process, and requires
        // the ability to override services.
        $services->set_allow_override(true);
        parent::configure_service_manager($services);
        $services->set_allow_override(false);
        return $services;
    }
    /**
     * Return all service configuration
     *
     * @return array
     */
    public function to_array()
    {
        return $this->config;
    }
}