<?php

declare (strict_types=1);
namespace Laminas\Mvc\Response_Sender;

use Laminas\Http\Response;
class Http_Response_Sender extends Abstract_Response_Sender
{
    /**
     * Send content
     */
    public function send_content(Send_Response_Event $event): static
    {
        if ($event->content_sent()) {
            return $this;
        }
        $response = $event->get_response();
        echo $response->get_content();
        $event->set_content_sent();
        return $this;
    }
    /**
     * Send HTTP response
     */
    public function __invoke(Send_Response_Event $event): static
    {
        $response = $event->get_response();
        if (!$response instanceof Response) {
            return $this;
        }
        $this->send_headers($event)->send_content($event);
        $event->stop_propagation(true);
        return $this;
    }
}