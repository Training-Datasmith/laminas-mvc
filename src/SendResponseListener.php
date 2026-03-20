<?php

declare (strict_types=1);
namespace Laminas\Mvc;

use Laminas\Event_Manager\Abstract_Listener_Aggregate;
use Laminas\Event_Manager\Event_Manager;
use Laminas\Event_Manager\Event_Manager_Aware_Interface;
use Laminas\Event_Manager\Event_Manager_Interface;
use Laminas\Mvc\Response_Sender\Http_Response_Sender;
use Laminas\Mvc\Response_Sender\Php_Environment_Response_Sender;
use Laminas\Mvc\Response_Sender\Send_Response_Event;
use Laminas\Mvc\Response_Sender\Simple_Stream_Response_Sender;
use Laminas\Stdlib\Response_Interface as Response;
class Send_Response_Listener extends Abstract_Listener_Aggregate implements Event_Manager_Aware_Interface
{
    /** @var SendResponseEvent */
    protected $event;
    /** @var EventManagerInterface */
    protected $event_manager;
    /**
     * Inject an EventManager instance
     *
     * @return SendResponseListener
     */
    public function set_event_manager(Event_Manager_Interface $event_manager)
    {
        $event_manager->set_identifiers([self::class, static::class]);
        $this->event_manager = $event_manager;
        $this->attach_default_listeners();
        return $this;
    }
    /**
     * Retrieve the event manager
     *
     * Lazy-loads an EventManager instance if none registered.
     *
     * @return EventManagerInterface
     */
    public function get_event_manager()
    {
        if (!$this->event_manager instanceof Event_Manager_Interface) {
            $this->set_event_manager(new Event_Manager());
        }
        return $this->event_manager;
    }
    /**
     * Attach the aggregate to the specified event manager
     *
     * @param  int $priority
     */
    public function attach(Event_Manager_Interface $events, $priority = 1): void
    {
        $this->listeners[] = $events->attach(Mvc_Event::EVENT_FINISH, $this->send_response(...), -10000);
    }
    /**
     * Send the response
     */
    public function send_response(Mvc_Event $e): void
    {
        $response = $e->get_response();
        if (!$response instanceof Response) {
            return;
            // there is no response to send
        }
        $event = $this->get_event();
        $event->set_response($response);
        $event->set_target($this);
        $this->get_event_manager()->trigger_event($event);
    }
    /**
     * Get the send response event
     *
     * @return SendResponseEvent
     */
    public function get_event()
    {
        if (!$this->event instanceof Send_Response_Event) {
            $this->set_event(new Send_Response_Event());
        }
        return $this->event;
    }
    /**
     * Set the send response event
     *
     * @return SendResponseEvent
     */
    public function set_event(Send_Response_Event $e)
    {
        $this->event = $e;
        return $this;
    }
    /**
     * Register the default event listeners
     *
     * The order in which the response sender are listed here, is by their usage:
     * PhpEnvironmentResponseSender has highest priority, because it's used most often.
     * SimpleStreamResponseSender is not used that often, so has a lower priority.
     * You can attach your response sender before or after every default response sender implementation.
     * All default response sender implementation have negative priority.
     * You are able to attach listeners without giving a priority and your response sender would be first to try.
     */
    protected function attach_default_listeners()
    {
        $events = $this->get_event_manager();
        $events->attach(Send_Response_Event::EVENT_SEND_RESPONSE, new Php_Environment_Response_Sender(), -1000);
        $events->attach(Send_Response_Event::EVENT_SEND_RESPONSE, new Simple_Stream_Response_Sender(), -3000);
        $events->attach(Send_Response_Event::EVENT_SEND_RESPONSE, new Http_Response_Sender(), -4000);
    }
}