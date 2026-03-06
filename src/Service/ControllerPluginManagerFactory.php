<?php

declare(strict_types=1);

namespace Laminas\Mvc\Service;

use Laminas\Mvc\Controller\PluginManager as ControllerPluginManager;

class ControllerPluginManagerFactory extends AbstractPluginManagerFactory
{
    public const PLUGIN_MANAGER_CLASS = ControllerPluginManager::class;
}
