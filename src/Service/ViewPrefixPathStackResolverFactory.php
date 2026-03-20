<?php

declare (strict_types=1);
namespace Laminas\Mvc\Service;

// phpcs:ignore
use Interop\Container\Container_Interface;
use Laminas\Service_Manager\Factory\Factory_Interface;
use Laminas\View\Resolver\Prefix_Path_Stack_Resolver;
class View_Prefix_Path_Stack_Resolver_Factory implements Factory_Interface
{
    /**
     * Create the template prefix view resolver
     *
     * Creates a Laminas\View\Resolver\PrefixPathStackResolver and populates it with the
     * ['view_manager']['prefix_template_path_stack']
     *
     * @param  string $requestedName
     * @return PrefixPathStackResolver
     */
    public function __invoke(Container_Interface $container, $requested_name, ?array $options = null)
    {
        $config = $container->get('config');
        $prefixes = [];
        if (isset($config['view_manager']['prefix_template_path_stack'])) {
            $prefixes = $config['view_manager']['prefix_template_path_stack'];
        }
        return new Prefix_Path_Stack_Resolver($prefixes);
    }
}