<?php

declare (strict_types=1);
namespace Laminas\Mvc\Service;

use Laminas\Paginator\Adapter_Plugin_Manager as PaginatorPluginManager;
class Paginator_Plugin_Manager_Factory extends Abstract_Plugin_Manager_Factory
{
    public const PLUGIN_MANAGER_CLASS = Paginator_Plugin_Manager::class;
}