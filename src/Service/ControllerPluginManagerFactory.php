<?php

declare (strict_types=1);
namespace Laminas\Mvc\Service;

use Laminas\Mvc\Controller\Plugin_Manager as ControllerPluginManager;
class Controller_Plugin_Manager_Factory extends Abstract_Plugin_Manager_Factory
{
    public const PLUGIN_MANAGER_CLASS = Controller_Plugin_Manager::class;
}