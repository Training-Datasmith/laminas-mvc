<?php

declare (strict_types=1);
namespace Laminas\Mvc\Service;

// phpcs:ignore
use Interop\Container\Container_Interface;
use function is_array;
use Laminas\Service_Manager\Factory\Factory_Interface;
use Laminas\View\Resolver as ViewResolver;
class View_Template_Path_Stack_Factory implements Factory_Interface
{
    /**
     * Create the template path stack view resolver
     *
     * Creates a Laminas\View\Resolver\TemplatePathStack and populates it with the
     * ['view_manager']['template_path_stack'] and sets the default suffix with the
     * ['view_manager']['default_template_suffix']
     *
     * @param  string $requestedName
     */
    public function __invoke(Container_Interface $container, $requested_name, ?array $options = null): \Laminas\View\Resolver\Template_Path_Stack
    {
        $config = $container->get('config');
        $template_path_stack = new View_Resolver\Template_Path_Stack();
        if (is_array($config) && isset($config['view_manager'])) {
            $config = $config['view_manager'];
            if (is_array($config)) {
                if (isset($config['template_path_stack'])) {
                    $template_path_stack->add_paths($config['template_path_stack']);
                }
                if (isset($config['default_template_suffix'])) {
                    $template_path_stack->set_default_suffix($config['default_template_suffix']);
                }
            }
        }
        return $template_path_stack;
    }
}