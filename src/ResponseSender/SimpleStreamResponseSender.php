<?php

declare (strict_types=1);
namespace Laminas\Mvc\Response_Sender;

use function fpassthru;
use Laminas\Http\Response\Stream;
class Simple_Stream_Response_Sender extends Abstract_Response_Sender
{
    /**
     * Send the stream
     *
     * @return SimpleStreamResponseSender
     */
    public function send_stream(Send_Response_Event $event)
    {
        if ($event->content_sent()) {
            return $this;
        }
        $response = $event->get_response();
        $stream = $response->get_stream();
        fpassthru($stream);
        $event->set_content_sent();
    }
    /**
     * Send stream response
     */
    public function __invoke(Send_Response_Event $event): static
    {
        $response = $event->get_response();
        if (!$response instanceof Stream) {
            return $this;
        }
        $this->send_headers($event);
        $this->send_stream($event);
        $event->stop_propagation(true);
        return $this;
    }
}