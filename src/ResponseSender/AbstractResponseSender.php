<?php

declare (strict_types=1);
namespace Laminas\Mvc\Response_Sender;

use function header;
use function headers_sent;
use function is_iterable;
use Laminas\Http\Header\Multiple_Header_Interface;
abstract class Abstract_Response_Sender implements Response_Sender_Interface
{
    /**
     * Send HTTP headers
     *
     * @return self
     */
    public function send_headers(Send_Response_Event $event)
    {
        if (headers_sent() || $event->headers_sent()) {
            return $this;
        }
        $response = $event->get_response();
        $headers = $response->get_headers();
        if (is_iterable($headers)) {
            foreach ($response->get_headers() as $header) {
                if ($header instanceof Multiple_Header_Interface) {
                    header($header->to_string(), false);
                    continue;
                }
                header($header->to_string());
            }
        }
        $status = $response->render_status_line();
        header((string) $status);
        $event->set_headers_sent();
        return $this;
    }
}