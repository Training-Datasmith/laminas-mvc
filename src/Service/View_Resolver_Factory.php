<?php

declare (strict_types=1);
namespace Laminas\Mvc\Service;

// phpcs:ignore
use Interop\Container\Container_Interface;
use Laminas\Service_Manager\Factory\Factory_Interface;
use Laminas\View\Resolver as ViewResolver;
class View_Resolver_Factory implements Factory_Interface
{
    /**
     * Create the aggregate view resolver
     *
     * Creates a Laminas\View\Resolver\AggregateResolver and attaches the template
     * map resolver and path stack resolver
     *
     * @param  string $requestedName
     */
    public function __invoke(Container_Interface $container, $requested_name, ?array $options = null): \Laminas\View\Resolver\Aggregate_Resolver
    {
        $resolver = new View_Resolver\Aggregate_Resolver();
        /** @var ResolverInterface $mapResolver */
        $map_resolver = $container->get('ViewTemplateMapResolver');
        /** @var ResolverInterface $pathResolver */
        $path_resolver = $container->get('ViewTemplatePathStack');
        /** @var ResolverInterface $prefixPathStackResolver */
        $prefix_path_stack_resolver = $container->get('ViewPrefixPathStackResolver');
        $resolver->attach($map_resolver)->attach($path_resolver)->attach($prefix_path_stack_resolver)->attach(new View_Resolver\Relative_Fallback_Resolver($map_resolver))->attach(new View_Resolver\Relative_Fallback_Resolver($path_resolver))->attach(new View_Resolver\Relative_Fallback_Resolver($prefix_path_stack_resolver));
        return $resolver;
    }
}