<?php

declare (strict_types=1);
namespace Laminas\Mvc\Service;

// phpcs:ignore
use Interop\Container\Container_Interface;
use Laminas\Service_Manager\Factory\Factory_Interface;
use Laminas\View\Strategy\Php_Renderer_Strategy;
use Laminas\View\View;
class View_Factory implements Factory_Interface
{
    /**
     * @param  string $requestedName
     */
    public function __invoke(Container_Interface $container, $requested_name, ?array $options = null): \Laminas\View\View
    {
        $view = new View();
        $events = $container->get('EventManager');
        $view->set_event_manager($events);
        $container->get(Php_Renderer_Strategy::class)->attach($events);
        return $view;
    }
}