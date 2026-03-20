<?php

declare (strict_types=1);
namespace Laminas\Mvc\Service;

// phpcs:ignore
use Interop\Container\Container_Interface;
use Laminas\Mvc\View\Http\Exception_Strategy;
use Laminas\Service_Manager\Factory\Factory_Interface;
class Http_Exception_Strategy_Factory implements Factory_Interface
{
    use Http_View_Manager_Config_Trait;
    /**
     * @param  string $requestedName
     */
    public function __invoke(Container_Interface $container, $requested_name, ?array $options = null): \Laminas\Mvc\View\Http\Exception_Strategy
    {
        $strategy = new Exception_Strategy();
        $config = $this->get_config($container);
        $this->inject_display_exceptions($strategy, $config);
        $this->inject_exception_template($strategy, $config);
        return $strategy;
    }
    /**
     * Inject strategy with configured display_exceptions flag.
     */
    private function inject_display_exceptions(Exception_Strategy $strategy, array $config): void
    {
        $flag = $config['display_exceptions'] ?? false;
        $strategy->set_display_exceptions($flag);
    }
    /**
     * Inject strategy with configured exception_template
     */
    private function inject_exception_template(Exception_Strategy $strategy, array $config): void
    {
        $template = $config['exception_template'] ?? 'error';
        $strategy->set_exception_template($template);
    }
}