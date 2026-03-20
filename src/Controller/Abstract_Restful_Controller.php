<?php

declare (strict_types=1);
namespace Laminas\Mvc\Controller;

use function array_key_exists;
use function array_shift;
use function call_user_func;
use function count;
use function explode;
use function get_debug_type;
use function is_array;
use function is_callable;
use function json_decode;
use Laminas\Http\Header\Content_Type;
use Laminas\Http\Request as HttpRequest;
use Laminas\Mvc\Exception;
use Laminas\Mvc\Exception\DomainException;
use Laminas\Mvc\Exception\InvalidArgumentException;
use Laminas\Mvc\Exception\RuntimeException;
use Laminas\Mvc\Mvc_Event;
use Laminas\Router\Route_Match;
use Laminas\Stdlib\Request_Interface as Request;
use Laminas\Stdlib\Response_Interface as Response;
use function method_exists;
use function parse_str;
use function reset;
use function sprintf;
use function str_contains;
use function stripos;
use function strtolower;
use function trim;
/**
 * Abstract RESTful controller
 */
abstract class Abstract_Restful_Controller extends Abstract_Controller
{
    public const CONTENT_TYPE_JSON = 'json';
    /**
     * {@inheritDoc}
     */
    protected $event_identifier = self::class;
    /** @var array */
    protected $content_types = [self::CONTENT_TYPE_JSON => ['application/hal+json', 'application/json']];
    /**
     * Name of request or query parameter containing identifier
     *
     * @var string
     */
    protected $identifier_name = 'id';
    /**
     * Flag to pass to json_decode.
     *
     * Default value is boolean true, meaning JSON should be cast to
     * associative arrays (vs objects).
     *
     * Override the value in an extending class to set the default behavior
     * for your class.
     *
     * @var bool
     */
    protected $json_decode_type = true;
    /**
     * Map of custom HTTP methods and their handlers
     *
     * @var array
     */
    protected $custom_http_methods_map = [];
    /**
     * Set the route match/query parameter name containing the identifier
     *
     * @param  string $name
     * @return self
     */
    public function set_identifier_name($name)
    {
        $this->identifier_name = (string) $name;
        return $this;
    }
    /**
     * Retrieve the route match/query parameter name containing the identifier
     *
     * @return string
     */
    public function get_identifier_name()
    {
        return $this->identifier_name;
    }
    /**
     * Create a new resource
     *
     * @return mixed
     */
    public function create(mixed $data)
    {
        $this->response->set_status_code(405);
        return ['content' => 'Method Not Allowed'];
    }
    /**
     * Delete an existing resource
     *
     * @return mixed
     */
    public function delete(mixed $id)
    {
        $this->response->set_status_code(405);
        return ['content' => 'Method Not Allowed'];
    }
    /**
     * Delete the entire resource collection
     *
     * Not marked as abstract, as that would introduce a BC break
     * (introduced in 2.1.0); instead, raises an exception if not implemented.
     *
     * @param mixed $data
     * @return mixed
     */
    public function delete_list($data)
    {
        $this->response->set_status_code(405);
        return ['content' => 'Method Not Allowed'];
    }
    /**
     * Return single resource
     *
     * @return mixed
     */
    public function get(mixed $id)
    {
        $this->response->set_status_code(405);
        return ['content' => 'Method Not Allowed'];
    }
    /**
     * Return list of resources
     *
     * @return mixed
     */
    public function get_list()
    {
        $this->response->set_status_code(405);
        return ['content' => 'Method Not Allowed'];
    }
    /**
     * Retrieve HEAD metadata for the resource
     *
     * Not marked as abstract, as that would introduce a BC break
     * (introduced in 2.1.0); instead, raises an exception if not implemented.
     *
     * @param  null|mixed $id
     * @return mixed
     */
    public function head($id = null)
    {
        $this->response->set_status_code(405);
        return ['content' => 'Method Not Allowed'];
    }
    /**
     * Respond to the OPTIONS method
     *
     * Typically, set the Allow header with allowed HTTP methods, and
     * return the response.
     *
     * Not marked as abstract, as that would introduce a BC break
     * (introduced in 2.1.0); instead, raises an exception if not implemented.
     *
     * @return mixed
     */
    public function options()
    {
        $this->response->set_status_code(405);
        return ['content' => 'Method Not Allowed'];
    }
    /**
     * Respond to the PATCH method
     *
     * Not marked as abstract, as that would introduce a BC break
     * (introduced in 2.1.0); instead, raises an exception if not implemented.
     *
     * @param mixed  $id
     * @param mixed $data
     * @return mixed
     */
    public function patch($id, $data)
    {
        $this->response->set_status_code(405);
        return ['content' => 'Method Not Allowed'];
    }
    /**
     * Replace an entire resource collection
     *
     * Not marked as abstract, as that would introduce a BC break
     * (introduced in 2.1.0); instead, raises an exception if not implemented.
     *
     * @return mixed
     */
    public function replace_list(mixed $data)
    {
        $this->response->set_status_code(405);
        return ['content' => 'Method Not Allowed'];
    }
    /**
     * Modify a resource collection without completely replacing it
     *
     * Not marked as abstract, as that would introduce a BC break
     * (introduced in 2.2.0); instead, raises an exception if not implemented.
     *
     * @return mixed
     */
    public function patch_list(mixed $data)
    {
        $this->response->set_status_code(405);
        return ['content' => 'Method Not Allowed'];
    }
    /**
     * Update an existing resource
     *
     * @return mixed
     */
    public function update(mixed $id, mixed $data)
    {
        $this->response->set_status_code(405);
        return ['content' => 'Method Not Allowed'];
    }
    /**
     * Basic functionality for when a page is not available
     *
     * @return array
     */
    public function not_found_action()
    {
        $this->response->set_status_code(404);
        return ['content' => 'Page not found'];
    }
    /**
     * Dispatch a request
     *
     * If the route match includes an "action" key, then this acts basically like
     * a standard action controller. Otherwise, it introspects the HTTP method
     * to determine how to handle the request, and which method to delegate to.
     *
     * @events dispatch.pre, dispatch.post
     * @return mixed|Response
     * @throws Exception\InvalidArgumentException
     */
    public function dispatch(Request $request, ?Response $response = null)
    {
        if (!$request instanceof Http_Request) {
            throw new InvalidArgumentException('Expected an HTTP request');
        }
        return parent::dispatch($request, $response);
    }
    /**
     * Handle the request
     *
     * @todo   try-catch in "patch" for patchList should be removed in the future
     * @return mixed
     * @throws Exception\DomainException If no route matches in event or invalid HTTP method.
     */
    public function on_dispatch(Mvc_Event $e)
    {
        $route_match = $e->get_route_match();
        if (!$route_match) {
            /**
             * @todo Determine requirements for when route match is missing.
             *       Potentially allow pulling directly from request metadata?
             */
            throw new DomainException('Missing route matches; unsure how to retrieve action');
        }
        $request = $e->get_request();
        // Was an "action" requested?
        $action = $route_match->get_param('action', false);
        if ($action) {
            // Handle arbitrary methods, ending in Action
            $method = static::get_method_from_action($action);
            if (!method_exists($this, $method)) {
                $method = 'notFoundAction';
            }
            $return = $this->{$method}();
            $e->set_result($return);
            return $return;
        }
        // RESTful methods
        $method = strtolower((string) $request->get_method());
        // Custom HTTP methods (or custom overrides for standard methods)
        if (isset($this->custom_http_methods_map[$method])) {
            $callable = $this->custom_http_methods_map[$method];
            $action = $method;
            $return = call_user_func($callable, $e);
            $route_match->set_param('action', $action);
            $e->set_result($return);
            return $return;
        }
        switch ($method) {
            // DELETE
            case 'delete':
                $id = $this->get_identifier($route_match, $request);
                if ($id !== false) {
                    $action = 'delete';
                    $return = $this->delete($id);
                    break;
                }
                $data = $this->process_body_content($request);
                $action = 'deleteList';
                $return = $this->delete_list($data);
                break;
            // GET
            case 'get':
                $id = $this->get_identifier($route_match, $request);
                if ($id !== false) {
                    $action = 'get';
                    $return = $this->get($id);
                    break;
                }
                $action = 'getList';
                $return = $this->get_list();
                break;
            // HEAD
            case 'head':
                $id = $this->get_identifier($route_match, $request);
                if ($id === false) {
                    $id = null;
                }
                $action = 'head';
                $head_result = $this->head($id);
                $response = $head_result instanceof Response ? clone $head_result : $e->get_response();
                $response->set_content('');
                $return = $response;
                break;
            // OPTIONS
            case 'options':
                $action = 'options';
                $this->options();
                $return = $e->get_response();
                break;
            // PATCH
            case 'patch':
                $id = $this->get_identifier($route_match, $request);
                $data = $this->process_body_content($request);
                if ($id !== false) {
                    $action = 'patch';
                    $return = $this->patch($id, $data);
                    break;
                }
                // TODO: This try-catch should be removed in the future, but it
                // will create a BC break for pre-2.2.0 apps that expect a 405
                // instead of going to patchList
                try {
                    $action = 'patchList';
                    $return = $this->patch_list($data);
                } catch (RuntimeException) {
                    $response = $e->get_response();
                    $response->set_status_code(405);
                    return $response;
                }
                break;
            // POST
            case 'post':
                $action = 'create';
                $return = $this->process_post_data($request);
                break;
            // PUT
            case 'put':
                $id = $this->get_identifier($route_match, $request);
                $data = $this->process_body_content($request);
                if ($id !== false) {
                    $action = 'update';
                    $return = $this->update($id, $data);
                    break;
                }
                $action = 'replaceList';
                $return = $this->replace_list($data);
                break;
            // All others...
            default:
                $response = $e->get_response();
                $response->set_status_code(405);
                return $response;
        }
        $route_match->set_param('action', $action);
        $e->set_result($return);
        return $return;
    }
    /**
     * Process post data and call create
     *
     * @return mixed
     * @throws Exception\DomainException If a JSON request was made, but no method for parsing JSON is available.
     */
    public function process_post_data(Request $request)
    {
        if ($this->request_has_content_type($request, self::CONTENT_TYPE_JSON)) {
            return $this->create($this->json_decode($request->get_content()));
        }
        return $this->create($request->get_post()->to_array());
    }
    /**
     * Check if request has certain content type
     *
     * @param  string|null $contentType
     * @return bool
     */
    public function request_has_content_type(Request $request, $content_type = '')
    {
        /** @var ContentType $headerContentType */
        $header_content_type = $request->get_headers()->get('content-type');
        if (!$header_content_type) {
            return false;
        }
        $requested_content_type = $header_content_type->get_field_value();
        if (str_contains((string) $requested_content_type, ';')) {
            $header_data = explode(';', (string) $requested_content_type);
            $requested_content_type = array_shift($header_data);
        }
        $requested_content_type = trim((string) $requested_content_type);
        if (array_key_exists($content_type, $this->content_types)) {
            foreach ($this->content_types[$content_type] as $content_type_value) {
                if (stripos((string) $requested_content_type, $content_type_value) === 0) {
                    return true;
                }
            }
        }
        return false;
    }
    /**
     * Register a handler for a custom HTTP method
     *
     * This method allows you to handle arbitrary HTTP method types, mapping
     * them to callables. Typically, these will be methods of the controller
     * instance: e.g., array($this, 'foobar'). The typical place to register
     * these is in your constructor.
     *
     * Additionally, as this map is checked prior to testing the standard HTTP
     * methods, this is a way to override what methods will handle the standard
     * HTTP methods. However, if you do this, you will have to retrieve the
     * identifier and any request content manually.
     *
     * Callbacks will be passed the current MvcEvent instance.
     *
     * To retrieve the identifier, you can use $id =this->getIdentifier($routeMatch, $request),
     * passing the appropriate objects.
     *
     * To retrieve the body content data, use $data = $this->processBodyContent($request);
     * that method will return a string, array, or, in the case of JSON, an object.
     *
     * @param string $method
     * @param Callable $handler
     * @return AbstractRestfulController
     */
    public function add_http_method_handler($method, $handler)
    {
        if (!is_callable($handler)) {
            throw new InvalidArgumentException(sprintf('Invalid HTTP method handler: must be a callable; received "%s"', get_debug_type($handler)));
        }
        $method = strtolower($method);
        $this->custom_http_methods_map[$method] = $handler;
        return $this;
    }
    /**
     * Retrieve the identifier, if any
     *
     * Attempts to see if an identifier was passed in either the URI or the
     * query string, returning it if found. Otherwise, returns a boolean false.
     *
     * @param RouteMatch $routeMatch
     * @param  Request $request
     * @return false|mixed
     */
    protected function get_identifier($route_match, $request)
    {
        $identifier = $this->get_identifier_name();
        $id = $route_match->get_param($identifier, false);
        if ($id !== false) {
            return $id;
        }
        $id = $request->get_query()->get($identifier, false);
        if ($id !== false) {
            return $id;
        }
        return false;
    }
    /**
     * Process the raw body content
     *
     * If the content-type indicates a JSON payload, the payload is immediately
     * decoded and the data returned. Otherwise, the data is passed to
     * parse_str(). If that function returns a single-member array with a empty
     * value, the method assumes that we have non-urlencoded content and
     * returns the raw content; otherwise, the array created is returned.
     *
     * @return object|string|array
     * @throws Exception\DomainException If a JSON request was made, but no method for parsing JSON is available.
     */
    protected function process_body_content(mixed $request)
    {
        $content = $request->get_content();
        // JSON content? decode and return it.
        if ($this->request_has_content_type($request, self::CONTENT_TYPE_JSON)) {
            return $this->json_decode($request->get_content());
        }
        parse_str((string) $content, $parsed_params);
        // If parse_str fails to decode, or we have a single element with empty value
        if (!is_array($parsed_params) || empty($parsed_params) || 1 === count($parsed_params) && '' === reset($parsed_params)) {
            return $content;
        }
        return $parsed_params;
    }
    /**
     * Decode a JSON string.
     *
     * Uses json_decode by default.
     *
     * Marked protected to allow usage from extending classes.
     *
     * @param string $string
     * @return mixed
     */
    protected function json_decode($string)
    {
        return json_decode($string, (bool) $this->json_decode_type);
    }
}