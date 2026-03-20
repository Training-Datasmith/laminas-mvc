<?php

declare (strict_types=1);
namespace Laminas\Mvc\Service;

// phpcs:ignore
use Interop\Container\Container_Interface;
use Laminas\Service_Manager\Factory\Factory_Interface;
use Laminas\View\Renderer\Php_Renderer;
class View_Php_Renderer_Factory implements Factory_Interface
{
    /**
     * @param  string $requestedName
     */
    public function __invoke(Container_Interface $container, $requested_name, ?array $options = null): \Laminas\View\Renderer\Php_Renderer
    {
        $renderer = new Php_Renderer();
        $renderer->set_helper_plugin_manager($container->get('ViewHelperManager'));
        $renderer->set_resolver($container->get('ViewResolver'));
        return $renderer;
    }
}