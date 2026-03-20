<?php

declare (strict_types=1);
namespace Laminas\Mvc;

use function in_array;
use Laminas\Event_Manager\Abstract_Listener_Aggregate;
use Laminas\Event_Manager\Event_Manager_Interface;
use Laminas\Http\Request as HttpRequest;
use Laminas\Http\Response as HttpResponse;
use function strtoupper;
/**
 * Listener that enforces an HTTP method allowlist at the route stage.
 *
 * Attaches to EVENT_ROUTE at priority 10000 (runs before the router) and returns
 * a 405 Method Not Allowed response for any request whose method is not in the
 * configured allowlist. When disabled, all methods are permitted.
 *
 * @since 3.0.0
 */
class Http_Method_Listener extends Abstract_Listener_Aggregate
{
    /**
     * The set of HTTP methods permitted by this listener.
     *
     * @var list<string> Uppercase HTTP method strings (e.g. 'GET', 'POST')
     */
    protected $allowed_methods = [Http_Request::METHOD_CONNECT, Http_Request::METHOD_DELETE, Http_Request::METHOD_GET, Http_Request::METHOD_HEAD, Http_Request::METHOD_OPTIONS, Http_Request::METHOD_PATCH, Http_Request::METHOD_POST, Http_Request::METHOD_PUT, Http_Request::METHOD_PROPFIND, Http_Request::METHOD_TRACE];
    /**
     * Whether this listener is active.
     *
     * When false, the listener does not attach to the event manager and all
     * HTTP methods are passed through without validation.
     */
    protected bool $enabled = true;
    /**
     * @param bool         $enabled         Whether the method filter is active
     * @param list<string> $allowed_methods  Optional override for the allowlist;
     *                                       values are uppercased automatically
     * @since 3.0.0
     */
    public function __construct(bool $enabled = true, array $allowed_methods = [])
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
     * Validate the HTTP method on the route event.
     *
     * Returns a 405 response immediately (short-circuiting the route chain) if the
     * request method is not in the allowlist. Only acts on HTTP requests/responses.
     *
     * @param Mvc_Event $e The MVC route event
     * @return HttpResponse|null Returns a 405 response to short-circuit routing, or null to continue
     * @since 3.0.0
     */
    public function on_route(Mvc_Event $e): ?HttpResponse
    {
        $request = $e->get_request();
        $response = $e->get_response();
        if (!$request instanceof Http_Request || !$response instanceof Http_Response) {
            return null;
        }
        $method = $request->get_method();
        if (in_array($method, $this->get_allowed_methods())) {
            return null;
        }
        $response->set_status_code(405);
        return $response;
    }
    /**
     * Return the list of allowed HTTP methods.
     *
     * @return list<string> Uppercase HTTP method strings
     * @since 3.0.0
     */
    public function get_allowed_methods(): array
    {
        return $this->allowed_methods;
    }
    /**
     * Replace the allowed HTTP methods allowlist.
     *
     * All provided values are uppercased to ensure case-insensitive matching.
     *
     * @param list<string> $allowed_methods Replacement allowlist (e.g. ['GET', 'POST'])
     * @since 3.0.0
     */
    public function set_allowed_methods(array $allowed_methods): void
    {
        foreach ($allowed_methods as &$value) {
            $value = strtoupper((string) $value);
        }
        $this->allowed_methods = $allowed_methods;
    }
    /**
     * Determine whether the HTTP method listener is active.
     *
     * @return bool True if the listener will enforce the allowlist
     * @since 3.0.0
     */
    public function is_enabled(): bool
    {
        return $this->enabled;
    }
    /**
     * Enable or disable the HTTP method listener.
     *
     * When set to false, the listener will not attach to the event manager
     * and all HTTP methods will be permitted.
     *
     * @param bool $enabled True to enforce the allowlist, false to disable
     * @since 3.0.0
     */
    public function set_enabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }
}