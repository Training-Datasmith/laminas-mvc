<?php

declare (strict_types=1);
namespace Laminas\Mvc\Service;

// phpcs:ignore
use Interop\Container\Container_Interface;
use function is_array;
use Laminas\Mvc\View\Http\Inject_Template_Listener;
use Laminas\Service_Manager\Factory\Factory_Interface;
class Inject_Template_Listener_Factory implements Factory_Interface
{
    /**
     * {@inheritDoc}
     *
     * Create and return an InjectTemplateListener instance.
     */
    public function __invoke(Container_Interface $container, $requested_name, ?array $options = null): \Laminas\Mvc\View\Http\Inject_Template_Listener
    {
        $listener = new Inject_Template_Listener();
        $config = $container->get('config');
        if (isset($config['view_manager']['controller_map']) && is_array($config['view_manager']['controller_map'])) {
            $listener->set_controller_map($config['view_manager']['controller_map']);
        }
        return $listener;
    }
}