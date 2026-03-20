<?php

declare (strict_types=1);
namespace Laminas\Mvc\Service;

// phpcs:ignore
use Interop\Container\Container_Interface;
use Laminas\Event_Manager\Event_Manager;
use Laminas\Service_Manager\Factory\Factory_Interface;
class Event_Manager_Factory implements Factory_Interface
{
    /**
     * Create an EventManager instance
     *
     * Creates a new EventManager instance, seeding it with a shared instance
     * of SharedEventManager.
     *
     * @param  string $requestedName
     * @return EventManager
     */
    public function __invoke(Container_Interface $container, $requested_name, ?array $options = null)
    {
        $shared = $container->has('SharedEventManager') ? $container->get('SharedEventManager') : null;
        return new Event_Manager($shared);
    }
}