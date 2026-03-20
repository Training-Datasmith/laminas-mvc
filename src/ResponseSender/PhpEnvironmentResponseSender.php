<?php

declare (strict_types=1);
namespace Laminas\Mvc\Response_Sender;

use Laminas\Http\Php_Environment\Response;
class Php_Environment_Response_Sender extends Http_Response_Sender
{
    /**
     * Send php environment response
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