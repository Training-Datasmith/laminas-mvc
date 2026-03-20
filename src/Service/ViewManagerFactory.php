<?php

declare (strict_types=1);
namespace Laminas\Mvc\Service;

// phpcs:ignore
use Interop\Container\Container_Interface;
use Laminas\Mvc\View\Http\View_Manager as HttpViewManager;
use Laminas\Service_Manager\Factory\Factory_Interface;
class View_Manager_Factory implements Factory_Interface
{
    /**
     * Create and return a view manager.
     *
     * @param  string $requestedName
     * @return HttpViewManager
     */
    public function __invoke(Container_Interface $container, $requested_name, ?array $options = null)
    {
        return $container->get('HttpViewManager');
    }
}