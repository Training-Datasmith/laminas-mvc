<?php

declare (strict_types=1);
namespace Laminas\Mvc\Response_Sender;

use Laminas\Event_Manager\Event;
use Laminas\Stdlib\Response_Interface;
use function spl_object_hash;
class Send_Response_Event extends Event
{
    /**#@+
     * Send response events triggered by eventmanager
     */
    public const EVENT_SEND_RESPONSE = 'sendResponse';
    /**#@-*/
    /** @var string Event name */
    protected $name = 'sendResponse';
    /** @var ResponseInterface */
    protected $response;
    /** @var array */
    protected $headers_sent = [];
    /** @var array */
    protected $content_sent = [];
    /**
     * @return SendResponseEvent
     */
    public function set_response(Response_Interface $response)
    {
        $this->set_param('response', $response);
        $this->response = $response;
        return $this;
    }
    /**
     * @return ResponseInterface
     */
    public function get_response()
    {
        return $this->response;
    }
    /**
     * Set content sent for current response
     *
     * @return SendResponseEvent
     */
    public function set_content_sent()
    {
        $response = $this->get_response();
        $content_sent = $this->get_param('contentSent', []);
        $response_object_hash = spl_object_hash($response);
        $content_sent[$response_object_hash] = true;
        $this->set_param('contentSent', $content_sent);
        $this->content_sent[$response_object_hash] = true;
        return $this;
    }
    /**
     * @return bool
     */
    public function content_sent()
    {
        $response = $this->get_response();
        if (isset($this->content_sent[spl_object_hash($response)])) {
            return true;
        }
        return false;
    }
    /**
     * Set headers sent for current response object
     *
     * @return SendResponseEvent
     */
    public function set_headers_sent()
    {
        $response = $this->get_response();
        $headers_sent = $this->get_param('headersSent', []);
        $response_object_hash = spl_object_hash($response);
        $headers_sent[$response_object_hash] = true;
        $this->set_param('headersSent', $headers_sent);
        $this->headers_sent[$response_object_hash] = true;
        return $this;
    }
    /**
     * @return bool
     */
    public function headers_sent()
    {
        $response = $this->get_response();
        if (isset($this->headers_sent[spl_object_hash($response)])) {
            return true;
        }
        return false;
    }
}