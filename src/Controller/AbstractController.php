<?php

declare (strict_types=1);
namespace Laminas\Mvc\Controller;

use function array_merge;
use function array_values;
use function call_user_func_array;
use function class_implements;
use function is_callable;
use Laminas\Event_Manager\Event_Interface as Event;
use Laminas\Event_Manager\Event_Manager;
use Laminas\Event_Manager\Event_Manager_Aware_Interface;
use Laminas\Event_Manager\Event_Manager_Interface;
use Laminas\Http\Header\Accept\Field_Value_Part\Abstract_Field_Value_Part;
use Laminas\Http\Php_Environment\Response as HttpResponse;
use Laminas\Http\Request as HttpRequest;
use Laminas\Mvc\Controller\Plugin\Forward;
use Laminas\Mvc\Controller\Plugin\Layout;
use Laminas\Mvc\Controller\Plugin\Params;
use Laminas\Mvc\Controller\Plugin\Redirect;
use Laminas\Mvc\Controller\Plugin\Url;
use Laminas\Mvc\Inject_Application_Event_Interface;
use Laminas\Mvc\Mvc_Event;
use Laminas\Service_Manager\Service_Manager;
use Laminas\Stdlib\Dispatchable_Interface as Dispatchable;
use Laminas\Stdlib\Request_Interface as Request;
use Laminas\Stdlib\Response_Interface as Response;
use Laminas\View\Model\Model_Interface;
use Laminas\View\Model\View_Model;
use function lcfirst;
use function str_replace;
use function strrpos;
use function strstr;
use function substr;
use function ucwords;
/**
 * Abstract controller
 *
 * Convenience methods for pre-built plugins (@see __call):
 * @codingStandardsIgnoreStart
 * @method ModelInterface acceptableViewModelSelector(array $matchAgainst = null, bool $returnDefault = true, AbstractFieldValuePart $resultReference = null)
 * @codingStandardsIgnoreEnd
 * @method Forward forward()
 * @method Layout|ModelInterface layout(string $template = null)
 * @method Params|mixed params(string $param = null, mixed $default = null)
 * @method Redirect redirect()
 * @method Url url()
 * @method ViewModel createHttpNotFoundModel(Response $response)
 */
abstract class Abstract_Controller implements Dispatchable, Event_Manager_Aware_Interface, Inject_Application_Event_Interface
{
    /** @var PluginManager */
    protected $plugins;
    /** @var Request */
    protected $request;
    /** @var Response */
    protected $response;
    /** @var Event */
    protected $event;
    /** @var EventManagerInterface */
    protected $events;
    /** @var null|string|string[] */
    protected $event_identifier;
    /**
     * Execute the request
     *
     * @return mixed
     */
    abstract public function on_dispatch(Mvc_Event $e);
    /**
     * Dispatch a request
     *
     * @events dispatch.pre, dispatch.post
     * @return Response|mixed
     */
    public function dispatch(Request $request, ?Response $response = null)
    {
        $this->request = $request;
        if (!$response) {
            $response = new Http_Response();
        }
        $this->response = $response;
        $e = $this->get_event();
        $e->set_name(Mvc_Event::EVENT_DISPATCH);
        $e->set_request($request);
        $e->set_response($response);
        $e->set_target($this);
        $result = $this->get_event_manager()->trigger_event_until(static fn($test): bool => $test instanceof Response, $e);
        if ($result->stopped()) {
            return $result->last();
        }
        return $e->get_result();
    }
    /**
     * Get request object
     *
     * @return Request
     */
    public function get_request()
    {
        if (!$this->request) {
            $this->request = new Http_Request();
        }
        return $this->request;
    }
    /**
     * Get response object
     *
     * @return Response
     */
    public function get_response()
    {
        if (!$this->response) {
            $this->response = new Http_Response();
        }
        return $this->response;
    }
    /**
     * Set the event manager instance used by this context
     *
     * @return AbstractController
     */
    public function set_event_manager(Event_Manager_Interface $events)
    {
        $class_name = static::class;
        $identifiers = [self::class, $class_name];
        $rightmost_ns_pos = strrpos($class_name, '\\');
        if ($rightmost_ns_pos !== false) {
            $identifiers[] = strstr($class_name, '\\', true);
            // top namespace
            $identifiers[] = substr($class_name, 0, $rightmost_ns_pos);
            // full namespace
        }
        $events->set_identifiers(array_merge($identifiers, array_values(class_implements($class_name)), (array) $this->event_identifier));
        $this->events = $events;
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
        if (!$this->events) {
            $this->set_event_manager(new Event_Manager());
        }
        return $this->events;
    }
    /**
     * Set an event to use during dispatch
     *
     * By default, will re-cast to MvcEvent if another event type is provided.
     */
    public function set_event(Event $e): void
    {
        if (!$e instanceof Mvc_Event) {
            $event_params = $e->get_params();
            $e = new Mvc_Event();
            $e->set_params($event_params);
            unset($event_params);
        }
        $this->event = $e;
    }
    /**
     * Get the attached event
     *
     * Will create a new MvcEvent if none provided.
     *
     * @return MvcEvent
     */
    public function get_event()
    {
        if (!$this->event) {
            $this->set_event(new Mvc_Event());
        }
        return $this->event;
    }
    /**
     * Get plugin manager
     *
     * @return PluginManager
     */
    public function get_plugin_manager()
    {
        if (!$this->plugins) {
            $this->set_plugin_manager(new Plugin_Manager(new Service_Manager()));
        }
        $this->plugins->set_controller($this);
        return $this->plugins;
    }
    /**
     * Set plugin manager
     *
     * @return AbstractController
     */
    public function set_plugin_manager(Plugin_Manager $plugins)
    {
        $this->plugins = $plugins;
        $this->plugins->set_controller($this);
        return $this;
    }
    /**
     * Get plugin instance
     *
     * @param  string     $name    Name of plugin to return
     * @param  null|array $options Options to pass to plugin constructor (if not already instantiated)
     * @return mixed
     */
    public function plugin($name, ?array $options = null)
    {
        return $this->get_plugin_manager()->get($name, $options);
    }
    /**
     * Method overloading: return/call plugins
     *
     * If the plugin is a functor, call it, passing the parameters provided.
     * Otherwise, return the plugin instance.
     *
     * @param  array  $params
     * @return mixed
     */
    public function __call(string $method, array $params)
    {
        $plugin = $this->plugin($method);
        if (is_callable($plugin)) {
            return call_user_func_array($plugin, $params);
        }
        return $plugin;
    }
    /**
     * Register the default events for this controller
     *
     * @return void
     */
    protected function attach_default_listeners()
    {
        $events = $this->get_event_manager();
        $events->attach(Mvc_Event::EVENT_DISPATCH, $this->on_dispatch(...));
    }
    /**
     * Transform an "action" token into a method name
     *
     * @param  string $action
     * @return string
     */
    public static function get_method_from_action($action)
    {
        $method = str_replace(['.', '-', '_'], ' ', $action);
        $method = ucwords($method);
        $method = str_replace(' ', '', $method);
        $method = lcfirst($method);
        return $method . 'Action';
    }
}