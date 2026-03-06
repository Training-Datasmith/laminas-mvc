<?php

namespace Laminas\Mvc\Service;

// phpcs:ignore
use Interop\Container\ContainerInterface;
use Laminas\Mvc\HttpMethodListener;
use Laminas\ServiceManager\Factory\FactoryInterface;

use function array_key_exists;
use function is_array;

class HttpMethodListenerFactory implements FactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): \Laminas\Mvc\HttpMethodListener
    {
        $config = $container->get('config');

        if (! isset($config['http_methods_listener'])) {
            return new HttpMethodListener();
        }

        $listenerConfig = $config['http_methods_listener'];
        $enabled        = array_key_exists('enabled', $listenerConfig)
            ? $listenerConfig['enabled']
            : true;
        $allowedMethods = isset($listenerConfig['allowed_methods']) && is_array($listenerConfig['allowed_methods'])
            ? $listenerConfig['allowed_methods']
            : null;

        return new HttpMethodListener($enabled, $allowedMethods);
    }
}
