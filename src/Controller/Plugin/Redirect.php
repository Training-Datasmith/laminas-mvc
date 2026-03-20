<?php

declare (strict_types=1);
namespace Laminas\Mvc\Controller\Plugin;

use function is_scalar;
use function ltrim;
use function preg_match;
use function str_starts_with;
use Laminas\Http\Response;
use Laminas\Mvc\Exception;
use Laminas\Mvc\Exception\DomainException;
use Laminas\Mvc\Inject_Application_Event_Interface;
use Laminas\Mvc\Mvc_Event;
use function method_exists;
class Redirect extends Abstract_Plugin
{
    /** @var MvcEvent|null */
    protected $event;
    /** @var Response|null */
    protected $response;
    /**
     * Generate redirect response based on given route
     *
     * @param  string $route RouteInterface name
     * @param  array $params Parameters to use in url generation, if any
     * @param  array $options RouteInterface-specific options to use in url generation, if any
     * @param  bool $reuseMatchedParams Whether to reuse matched parameters
     * @return Response
     * @throws Exception\DomainException If composed controller does not implement InjectApplicationEventInterface, or
     *         router cannot be found in controller event.
     */
    public function to_route($route = null, $params = [], $options = [], $reuse_matched_params = false)
    {
        $controller = $this->get_controller();
        if (!$controller || !method_exists($controller, 'plugin')) {
            throw new DomainException('Redirect plugin requires a controller that defines the plugin() method');
        }
        $url_plugin = $controller->plugin('url');
        if (is_scalar($options)) {
            $url = $url_plugin->from_route($route, $params, $options);
        } else {
            $url = $url_plugin->from_route($route, $params, $options, $reuse_matched_params);
        }
        return $this->to_url($url);
    }
    /**
     * Generate redirect response based on given URL
     *
     * @param  string $url
     * @return Response
     */
    public function to_url($url)
    {
        // Reject javascript:, data: schemes and protocol-relative URLs to prevent open redirect
        if (preg_match('#^\s*(javascript|data):#i', $url) || str_starts_with(ltrim($url), '//')) {
            throw new DomainException(sprintf('Redirect URL "%s" is not allowed; javascript:, data:, and protocol-relative URLs are forbidden.', $url));
        }
        $response = $this->get_response();
        $response->get_headers()->add_header_line('Location', $url);
        $response->set_status_code(302);
        return $response;
    }
    /**
     * Refresh to current route
     *
     * @return Response
     */
    public function refresh()
    {
        return $this->to_route(null, [], [], true);
    }
    /**
     * Get the response
     *
     * @return Response
     * @throws Exception\DomainException If unable to find response.
     */
    protected function get_response()
    {
        if ($this->response) {
            return $this->response;
        }
        $event = $this->get_event();
        $response = $event->get_response();
        if (!$response instanceof Response) {
            throw new DomainException('Redirect plugin requires event compose a response');
        }
        $this->response = $response;
        return $this->response;
    }
    /**
     * Get the event
     *
     * @return MvcEvent
     * @throws Exception\DomainException If unable to find event.
     */
    protected function get_event()
    {
        if ($this->event) {
            return $this->event;
        }
        $controller = $this->get_controller();
        if (!$controller instanceof Inject_Application_Event_Interface) {
            throw new DomainException('Redirect plugin requires a controller that implements InjectApplicationEventInterface');
        }
        $event = $controller->get_event();
        if (!$event instanceof Mvc_Event) {
            $params = $event->get_params();
            $event = new Mvc_Event();
            $event->set_params($params);
        }
        $this->event = $event;
        return $this->event;
    }
}