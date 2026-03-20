<?php

declare (strict_types=1);
namespace Laminas\Mvc\Service;

// phpcs:ignore
use Interop\Container\Container_Interface;
use Laminas\Mvc\Dispatch_Listener;
use Laminas\Service_Manager\Factory\Factory_Interface;
class Dispatch_Listener_Factory implements Factory_Interface
{
    /**
     * Create the default dispatch listener.
     *
     * @param  string $requestedName
     */
    public function __invoke(Container_Interface $container, $requested_name, ?array $options = null): \Laminas\Mvc\Dispatch_Listener
    {
        return new Dispatch_Listener($container->get('ControllerManager'));
    }
}