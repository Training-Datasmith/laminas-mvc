<?php

declare(strict_types=1);

/**
 * Example: Attaching a custom listener to the MVC bootstrap event.
 *
 * Laminas MVC is entirely event-driven. This example shows how to hook into
 * the bootstrap event to attach your own listener to the dispatch cycle —
 * a common pattern for cross-cutting concerns such as authentication,
 * locale detection, and logging.
 *
 * Typically placed in your Module.php::onBootstrap() method.
 */

use Laminas\EventManager\EventInterface;
use Laminas\Mvc\Application;
use Laminas\Mvc\Mvc_Event;
use Laminas\Stdlib\ResponseInterface;

/**
 * A simple timing listener that measures total dispatch time.
 */
class Dispatch_Timer_Listener
{
    private float $start_time;

    /**
     * Attach this listener to the application's event manager.
     *
     * Call this from Module::onBootstrap($e):
     *   $listener = new Dispatch_Timer_Listener();
     *   $listener->attach($e->getApplication()->getEventManager());
     */
    public function attach(\Laminas\EventManager\EventManagerInterface $events): void
    {
        $events->attach(Mvc_Event::EVENT_ROUTE, [$this, 'on_route'], 1000);
        $events->attach(Mvc_Event::EVENT_FINISH, [$this, 'on_finish'], -9999);
    }

    /**
     * Record the start time before routing begins.
     */
    public function on_route(EventInterface $event): void
    {
        $this->start_time = microtime(true);
    }

    /**
     * Log the total dispatch time at the finish event.
     *
     * @param EventInterface $event The MVC finish event
     */
    public function on_finish(EventInterface $event): void
    {
        $elapsed = microtime(true) - $this->start_time;
        $ms      = number_format($elapsed * 1000, 2);
        error_log("Dispatch completed in {$ms}ms");
    }
}

// --- Usage in Module.php ---
//
// class Module
// {
//     public function onBootstrap(\Laminas\Mvc\MvcEvent $e): void
//     {
//         $timer = new Dispatch_Timer_Listener();
//         $timer->attach($e->getApplication()->getEventManager());
//     }
// }

echo "See comments in the file for usage in a Module::onBootstrap() method." . PHP_EOL;
