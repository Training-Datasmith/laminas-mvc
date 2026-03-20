<?php

declare (strict_types=1);
namespace Laminas\Mvc\Service;

// phpcs:ignore
use Interop\Container\Container_Interface;
use Laminas\Mvc\View\Http\Route_Not_Found_Strategy;
use Laminas\Service_Manager\Factory\Factory_Interface;
class Http_Route_Not_Found_Strategy_Factory implements Factory_Interface
{
    use Http_View_Manager_Config_Trait;
    /**
     * @param  string $requestedName
     */
    public function __invoke(Container_Interface $container, $requested_name, ?array $options = null): \Laminas\Mvc\View\Http\Route_Not_Found_Strategy
    {
        $strategy = new Route_Not_Found_Strategy();
        $config = $this->get_config($container);
        $this->inject_display_exceptions($strategy, $config);
        $this->inject_display_not_found_reason($strategy, $config);
        $this->inject_not_found_template($strategy, $config);
        return $strategy;
    }
    /**
     * Inject strategy with configured display_exceptions flag.
     */
    private function inject_display_exceptions(Route_Not_Found_Strategy $strategy, array $config): void
    {
        $flag = $config['display_exceptions'] ?? false;
        $strategy->set_display_exceptions($flag);
    }
    /**
     * Inject strategy with configured display_not_found_reason flag.
     */
    private function inject_display_not_found_reason(Route_Not_Found_Strategy $strategy, array $config): void
    {
        $flag = $config['display_not_found_reason'] ?? false;
        $strategy->set_display_not_found_reason($flag);
    }
    /**
     * Inject strategy with configured not_found_template.
     */
    private function inject_not_found_template(Route_Not_Found_Strategy $strategy, array $config): void
    {
        $template = $config['not_found_template'] ?? '404';
        $strategy->set_not_found_template($template);
    }
}