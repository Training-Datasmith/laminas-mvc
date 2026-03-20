<?php

declare (strict_types=1);
namespace Laminas\Mvc\Service;

// phpcs:ignore
use Interop\Container\Container_Interface;
use Laminas\Mvc\View\Http\Default_Rendering_Strategy;
use Laminas\Service_Manager\Factory\Factory_Interface;
use Laminas\View\View;
class Http_Default_Rendering_Strategy_Factory implements Factory_Interface
{
    use Http_View_Manager_Config_Trait;
    /**
     * @param  string $requestedName
     */
    public function __invoke(Container_Interface $container, $requested_name, ?array $options = null): \Laminas\Mvc\View\Http\Default_Rendering_Strategy
    {
        $strategy = new Default_Rendering_Strategy($container->get(View::class));
        $config = $this->get_config($container);
        $this->inject_layout_template($strategy, $config);
        return $strategy;
    }
    /**
     * Inject layout template.
     *
     * Uses layout template from configuration; if none available, defaults to "layout/layout".
     */
    private function inject_layout_template(Default_Rendering_Strategy $strategy, array $config): void
    {
        $layout = $config['layout'] ?? 'layout/layout';
        $strategy->set_layout_template($layout);
    }
}