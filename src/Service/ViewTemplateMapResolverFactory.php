<?php

declare(strict_types=1);

namespace Laminas\Mvc\Service;

// phpcs:ignore
use Interop\Container\ContainerInterface;

use function is_array;

use Laminas\ServiceManager\Factory\FactoryInterface;

use Laminas\View\Resolver as ViewResolver;

class ViewTemplateMapResolverFactory implements FactoryInterface
{
    /**
     * Create the template map view resolver
     *
     * Creates a Laminas\View\Resolver\AggregateResolver and populates it with the
     * ['view_manager']['template_map']
     *
     * @param  string $requestedName
     * @return ViewResolver\TemplateMapResolver
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $config = $container->get('config');
        $map    = [];
        if (is_array($config) && isset($config['view_manager'])) {
            $config = $config['view_manager'];
            if (is_array($config) && isset($config['template_map'])) {
                $map = $config['template_map'];
            }
        }
        return new ViewResolver\TemplateMapResolver($map);
    }
}
