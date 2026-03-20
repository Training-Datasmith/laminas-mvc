<?php

declare (strict_types=1);
namespace Laminas\Mvc\Service;

// phpcs:ignore
use Interop\Container\Container_Interface;
use Laminas\Mvc\Send_Response_Listener;
class Send_Response_Listener_Factory
{
    public function __invoke(Container_Interface $container): \Laminas\Mvc\Send_Response_Listener
    {
        $listener = new Send_Response_Listener();
        $listener->set_event_manager($container->get('EventManager'));
        return $listener;
    }
}