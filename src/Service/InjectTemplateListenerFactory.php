<?php

declare(strict_types=1);

namespace Laminas\Mvc\Service;

// phpcs:ignore
use Interop\Container\ContainerInterface;

use function is_array;

use Laminas\Mvc\View\Http\InjectTemplateListener;

use Laminas\ServiceManager\Factory\FactoryInterface;

class InjectTemplateListenerFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     *
     * Create and return an InjectTemplateListener instance.
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): \Laminas\Mvc\View\Http\InjectTemplateListener
    {
        $listener = new InjectTemplateListener();
        $config   = $container->get('config');

        if (
            isset($config['view_manager']['controller_map'])
            && (is_array($config['view_manager']['controller_map']))
        ) {
            $listener->setControllerMap($config['view_manager']['controller_map']);
        }

        return $listener;
    }
}
