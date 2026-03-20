<?php

declare (strict_types=1);
namespace Laminas\Mvc;

use Laminas\Event_Manager\Event_Interface as Event;
interface Inject_Application_Event_Interface
{
    /**
     * Compose an Event
     *
     * @return void
     */
    public function set_event(Event $event);
    /**
     * Retrieve the composed event
     *
     * @return Event
     */
    public function get_event();
}