<?php

namespace Laminas\Mvc\Service;

// phpcs:ignore
use Interop\Container\ContainerInterface;
use Laminas\Mvc\DispatchListener;
use Laminas\ServiceManager\Factory\FactoryInterface;

class DispatchListenerFactory implements FactoryInterface
{
    /**
     * Create the default dispatch listener.
     *
     * @param  string $requestedName
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): \Laminas\Mvc\DispatchListener
    {
        return new DispatchListener($container->get('ControllerManager'));
    }
}
