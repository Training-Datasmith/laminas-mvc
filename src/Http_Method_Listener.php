<?php

declare (strict_types=1);
namespace Laminas\Mvc;

use function in_array;
use Laminas\Event_Manager\Abstract_Listener_Aggregate;
use Laminas\Event_Manager\Event_Manager_Interface;
use Laminas\Http\Request as HttpRequest;
use Laminas\Http\Response as HttpResponse;
use function strtoupper;
class Http_Method_Listener extends Abstract_Listener_Aggregate
{
    /** @var array */
    protected $allowed_methods = [Http_Request::METHOD_CONNECT, Http_Request::METHOD_DELETE, Http_Request::METHOD_GET, Http_Request::METHOD_HEAD, Http_Request::METHOD_OPTIONS, Http_Request::METHOD_PATCH, Http_Request::METHOD_POST, Http_Request::METHOD_PUT, Http_Request::METHOD_PROPFIND, Http_Request::METHOD_TRACE];
    /** @var bool */
    protected $enabled = true;
    /**
     * @param bool  $enabled
     * @param array $allowedMethods
     */
    public function __construct($enabled = true, $allowed_methods = [])
    {
        $this->set_enabled($enabled);
        if (!empty($allowed_methods)) {
            $this->set_allowed_methods($allowed_methods);
        }
    }
    /**
     * {@inheritdoc}
     */
    public function attach(Event_Manager_Interface $events, $priority = 1): void
    {
        if (!$this->is_enabled()) {
            return;
        }
        $this->listeners[] = $events->attach(Mvc_Event::EVENT_ROUTE, $this->on_route(...), 10000);
    }
    /**
     * @return void|HttpResponse
     */
    public function on_route(Mvc_Event $e)
    {
        $request = $e->get_request();
        $response = $e->get_response();
        if (!$request instanceof Http_Request || !$response instanceof Http_Response) {
            return;
        }
        $method = $request->get_method();
        if (in_array($method, $this->get_allowed_methods())) {
            return;
        }
        $response->set_status_code(405);
        return $response;
    }
    /**
     * @return array
     */
    public function get_allowed_methods()
    {
        return $this->allowed_methods;
    }
    public function set_allowed_methods(array $allowed_methods): void
    {
        foreach ($allowed_methods as &$value) {
            $value = strtoupper((string) $value);
        }
        $this->allowed_methods = $allowed_methods;
    }
    /**
     * @return bool
     */
    public function is_enabled()
    {
        return $this->enabled;
    }
    /**
     * @param bool $enabled
     */
    public function set_enabled($enabled): void
    {
        $this->enabled = (bool) $enabled;
    }
}