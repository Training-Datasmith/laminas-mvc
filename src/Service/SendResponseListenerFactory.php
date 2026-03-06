<?php

declare(strict_types=1);

namespace Laminas\Mvc\Service;

// phpcs:ignore
use Interop\Container\ContainerInterface;
use Laminas\Mvc\SendResponseListener;

class SendResponseListenerFactory
{
    public function __invoke(ContainerInterface $container): \Laminas\Mvc\SendResponseListener
    {
        $listener = new SendResponseListener();
        $listener->setEventManager($container->get('EventManager'));
        return $listener;
    }
}
