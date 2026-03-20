<?php

declare (strict_types=1);
namespace Laminas\Mvc\Service;

// phpcs:ignore
use Interop\Container\Container_Interface;
use Laminas\Service_Manager\Factory\Factory_Interface;
use Traversable;
class Config_Factory implements Factory_Interface
{
    /**
     * Create the application configuration service
     *
     * Retrieves the Module Manager from the service locator, and executes
     * {@link Laminas\ModuleManager\ModuleManager::loadModules()}.
     *
     * It then retrieves the config listener from the module manager, and from
     * that the merged configuration.
     *
     * @param string $requestedName
     * @return array|Traversable
     */
    public function __invoke(Container_Interface $container, $requested_name, ?array $options = null)
    {
        $module_manager = $container->get('ModuleManager');
        $module_manager->load_modules();
        $module_params = $module_manager->get_event()->get_params();
        return $module_params['configListener']->get_merged_config(false);
    }
}