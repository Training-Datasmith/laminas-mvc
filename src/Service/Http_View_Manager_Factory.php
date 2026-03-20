<?php

declare (strict_types=1);
namespace Laminas\Mvc\Service;

// phpcs:ignore
use Interop\Container\Container_Interface;
use Laminas\Mvc\View\Http\View_Manager as HttpViewManager;
use Laminas\Service_Manager\Factory\Factory_Interface;
class Http_View_Manager_Factory implements Factory_Interface
{
    /**
     * Create and return a view manager for the HTTP environment
     *
     * @param  string $requestedName
     */
    public function __invoke(Container_Interface $container, $requested_name, ?array $options = null): \Laminas\Mvc\View\Http\View_Manager
    {
        return new Http_View_Manager();
    }
}