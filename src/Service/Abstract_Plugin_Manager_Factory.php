<?php

declare (strict_types=1);
namespace Laminas\Mvc\Service;

// phpcs:ignore
use Interop\Container\Container_Interface;
use Laminas\Service_Manager\Abstract_Plugin_Manager;
use Laminas\Service_Manager\Factory\Factory_Interface;
abstract class Abstract_Plugin_Manager_Factory implements Factory_Interface
{
    public const PLUGIN_MANAGER_CLASS = 'AbstractPluginManager';
    /**
     * Create and return a plugin manager.
     *
     * Classes that extend this should provide a valid class for
     * the PLUGIN_MANGER_CLASS constant.
     *
     * @param  string $requestedName
     * @return AbstractPluginManager
     */
    public function __invoke(Container_Interface $container, $requested_name, ?array $options = null)
    {
        $options = $options ?: [];
        $plugin_manager_class = static::PLUGIN_MANAGER_CLASS;
        return new $plugin_manager_class($container, $options);
    }
}