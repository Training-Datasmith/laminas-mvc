<?php

declare (strict_types=1);
namespace Laminas\Mvc\Service;

// phpcs:ignore
use Interop\Container\Container_Interface;
use function is_array;
use Laminas\Service_Manager\Factory\Factory_Interface;
use Laminas\View\Resolver as ViewResolver;
class View_Template_Map_Resolver_Factory implements Factory_Interface
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
    public function __invoke(Container_Interface $container, $requested_name, ?array $options = null)
    {
        $config = $container->get('config');
        $map = [];
        if (is_array($config) && isset($config['view_manager'])) {
            $config = $config['view_manager'];
            if (is_array($config) && isset($config['template_map'])) {
                $map = $config['template_map'];
            }
        }
        return new View_Resolver\Template_Map_Resolver($map);
    }
}