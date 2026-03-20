<?php

declare (strict_types=1);
namespace Laminas\Mvc\Service;

// phpcs:ignore
use Interop\Container\Container_Interface;
use Laminas\Mvc\Controller\Controller_Manager;
use Laminas\Service_Manager\Factory\Factory_Interface;
class Controller_Manager_Factory implements Factory_Interface
{
    /**
     * Create the controller manager service
     *
     * Creates and returns an instance of ControllerManager. The
     * only controllers this manager will allow are those defined in the
     * application configuration's "controllers" array. If a controller is
     * matched, the scoped manager will attempt to load the controller.
     * Finally, it will attempt to inject the controller plugin manager
     * if the controller implements a setPluginManager() method.
     *
     * @param  string $requestedName
     */
    public function __invoke(Container_Interface $container, $requested_name, ?array $options = null): \Laminas\Mvc\Controller\Controller_Manager
    {
        if ($options) {
            return new Controller_Manager($container, $options);
        }
        return new Controller_Manager($container);
    }
}