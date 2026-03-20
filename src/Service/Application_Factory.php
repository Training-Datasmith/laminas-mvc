<?php

declare (strict_types=1);
namespace Laminas\Mvc\Service;

// phpcs:ignore
use Interop\Container\Container_Interface;
use Laminas\Mvc\Application;
use Laminas\Service_Manager\Factory\Factory_Interface;
class Application_Factory implements Factory_Interface
{
    /**
     * Create the Application service
     *
     * Creates a Laminas\Mvc\Application service, passing it the configuration
     * service and the service manager instance.
     *
     * @param  string $requestedName
     */
    public function __invoke(Container_Interface $container, $requested_name, ?array $options = null): \Laminas\Mvc\Application
    {
        return new Application($container, $container->get('EventManager'), $container->get('Request'), $container->get('Response'));
    }
}