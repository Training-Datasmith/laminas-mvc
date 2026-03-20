<?php

declare (strict_types=1);
namespace Laminas\Mvc\Controller;

// phpcs:ignore
use function get_debug_type;
use Interop\Container\Container_Interface;
use Laminas\Event_Manager\Event_Manager_Aware_Interface;
use Laminas\Event_Manager\Shared_Event_Manager_Interface;
use Laminas\Service_Manager\Abstract_Plugin_Manager;
use Laminas\Service_Manager\Config_Interface;
use Laminas\Service_Manager\Exception\Invalid_Service_Exception;
use Laminas\Stdlib\Dispatchable_Interface;
use function method_exists;
use function sprintf;
/**
 * Manager for loading controllers
 *
 * Does not define any controllers by default, but does add a validator.
 */
class Controller_Manager extends Abstract_Plugin_Manager
{
    /**
     * We do not want arbitrary classes instantiated as controllers.
     *
     * @var bool
     */
    protected $auto_add_invokable_class = false;
    /**
     * Controllers must be of this type.
     *
     * @var string
     */
    protected $instance_of = Dispatchable_Interface::class;
    /**
     * Constructor
     *
     * Injects an initializer for injecting controllers with an
     * event manager and plugin manager.
     *
     * @param  ConfigInterface|ContainerInterface $configOrContainerInstance
     */
    public function __construct($config_or_container_instance, array $config = [])
    {
        $this->add_initializer($this->inject_event_manager(...));
        $this->add_initializer($this->inject_plugin_manager(...));
        parent::__construct($config_or_container_instance, $config);
    }
    /**
     * Validate a plugin
     *
     * {@inheritDoc}
     */
    public function validate($plugin): void
    {
        if (!$plugin instanceof $this->instance_of) {
            throw new Invalid_Service_Exception(sprintf('Plugin of type "%s" is invalid; must implement %s', get_debug_type($plugin), $this->instance_of));
        }
    }
    /**
     * Initializer: inject EventManager instance
     *
     * If we have an event manager composed already, make sure it gets injected
     * with the shared event manager.
     *
     * The AbstractController lazy-instantiates an EM instance, which is why
     * the shared EM injection needs to happen; the conditional will always
     * pass.
     *
     * @param DispatchableInterface $controller
     */
    public function inject_event_manager(Container_Interface $container, $controller): void
    {
        if (!$controller instanceof Event_Manager_Aware_Interface) {
            return;
        }
        $events = $controller->get_event_manager();
        if (!$events || !$events->get_shared_manager() instanceof Shared_Event_Manager_Interface) {
            $controller->set_event_manager($container->get('EventManager'));
        }
    }
    /**
     * Initializer: inject plugin manager
     *
     * @param DispatchableInterface $controller
     */
    public function inject_plugin_manager(Container_Interface $container, $controller): void
    {
        if (!method_exists($controller, 'setPluginManager')) {
            return;
        }
        $controller->set_plugin_manager($container->get('ControllerPluginManager'));
    }
}