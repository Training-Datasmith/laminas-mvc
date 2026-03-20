<?php

declare (strict_types=1);
namespace Laminas\Mvc\Service;

// phpcs:ignore
use Interop\Container\Container_Interface;
use Laminas\Service_Manager\Factory\Factory_Interface;
use Laminas\View\Renderer\Php_Renderer;
use Laminas\View\Strategy\Php_Renderer_Strategy;
class View_Php_Renderer_Strategy_Factory implements Factory_Interface
{
    /**
     * @param  string $requestedName
     * @return PhpRendererStrategy
     */
    public function __invoke(Container_Interface $container, $requested_name, ?array $options = null)
    {
        return new Php_Renderer_Strategy($container->get(Php_Renderer::class));
    }
}