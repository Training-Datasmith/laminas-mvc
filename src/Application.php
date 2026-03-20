<?php

declare (strict_types=1);
namespace Laminas\Mvc;

use function array_merge;
use function array_unique;
use Laminas\Event_Manager\Event_Manager_Aware_Interface;
use Laminas\Event_Manager\Event_Manager_Interface;
use Laminas\Mvc\Service\Service_Manager_Config;
use Laminas\Service_Manager\Service_Manager;
use Laminas\Stdlib\Request_Interface;
use Laminas\Stdlib\Response_Interface;
/**
 * Main application class for invoking applications
 *
 * Expects the user will provide a configured ServiceManager, configured with
 * the following services:
 *
 * - EventManager
 * - ModuleManager
 * - Request
 * - Response
 * - RouteListener
 * - Router
 * - DispatchListener
 * - MiddlewareListener
 * - ViewManager
 *
 * The most common workflow is:
 * <code>
 * $services = new Laminas\ServiceManager\ServiceManager($servicesConfig);
 * $app      = new Application($appConfig, $services);
 * $app->bootstrap();
 * $response = $app->run();
 * $response->send();
 * </code>
 *
 * bootstrap() opts in to the default route, dispatch, and view listeners,
 * sets up the MvcEvent, and triggers the bootstrap event. This can be omitted
 * if you wish to setup your own listeners and/or workflow; alternately, you
 * can simply extend the class to override such behavior.
 */
class Application implements Application_Interface, Event_Manager_Aware_Interface
{
    public const ERROR_CONTROLLER_CANNOT_DISPATCH = 'error-controller-cannot-dispatch';
    public const ERROR_CONTROLLER_NOT_FOUND = 'error-controller-not-found';
    public const ERROR_CONTROLLER_INVALID = 'error-controller-invalid';
    public const ERROR_EXCEPTION = 'error-exception';
    public const ERROR_ROUTER_NO_MATCH = 'error-router-no-match';
    public const ERROR_MIDDLEWARE_CANNOT_DISPATCH = 'error-middleware-cannot-dispatch';
    /**
     * Default application event listeners
     *
     * @var array
     */
    protected $default_listeners = ['RouteListener', 'MiddlewareListener', 'DispatchListener', 'HttpMethodListener', 'ViewManager', 'SendResponseListener'];
    /**
     * MVC event token
     *
     * @var MvcEvent
     */
    protected $event;
    /** @var EventManagerInterface */
    protected $events;
    /** @var RequestInterface */
    protected $request;
    /** @var ResponseInterface */
    protected $response;
    /**
     * Constructor
     */
    public function __construct(protected Service_Manager $service_manager, ?Event_Manager_Interface $events = null, ?Request_Interface $request = null, ?Response_Interface $response = null)
    {
        $this->set_event_manager($events ?: $service_manager->get('EventManager'));
        $this->request = $request ?: $service_manager->get('Request');
        $this->response = $response ?: $service_manager->get('Response');
    }
    /**
     * Retrieve the application configuration
     *
     * @return array|object
     */
    public function get_config()
    {
        return $this->service_manager->get('config');
    }
    /**
     * Bootstrap the application
     *
     * Defines and binds the MvcEvent, and passes it the request, response, and
     * router. Attaches the ViewManager as a listener. Triggers the bootstrap
     * event.
     *
     * @param array $listeners List of listeners to attach.
     */
    public function bootstrap(array $listeners = []): static
    {
        $service_manager = $this->service_manager;
        $events = $this->events;
        // Setup default listeners
        $listeners = array_unique(array_merge($this->default_listeners, $listeners));
        foreach ($listeners as $listener) {
            $service_manager->get($listener)->attach($events);
        }
        // Setup MVC Event
        $this->event = $event = new Mvc_Event();
        $event->set_name(Mvc_Event::EVENT_BOOTSTRAP);
        $event->set_target($this);
        $event->set_application($this);
        $event->set_request($this->request);
        $event->set_response($this->response);
        $event->set_router($service_manager->get('Router'));
        // Trigger bootstrap events
        $events->trigger_event($event);
        return $this;
    }
    /**
     * Retrieve the service manager
     */
    public function get_service_manager(): \Laminas\Service_Manager\Service_Manager
    {
        return $this->service_manager;
    }
    /**
     * Get the request object
     *
     * @return RequestInterface
     */
    public function get_request()
    {
        return $this->request;
    }
    /**
     * Get the response object
     *
     * @return ResponseInterface
     */
    public function get_response()
    {
        return $this->response;
    }
    /**
     * Get the MVC event instance
     *
     * @return MvcEvent
     */
    public function get_mvc_event()
    {
        return $this->event;
    }
    /**
     * Set the event manager instance
     */
    public function set_event_manager(Event_Manager_Interface $event_manager): static
    {
        $event_manager->set_identifiers([self::class, static::class]);
        $this->events = $event_manager;
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
        return $this->events;
    }
    /**
     * Static method for quick and easy initialization of the Application.
     *
     * If you use this init() method, you cannot specify a service with the
     * name of 'ApplicationConfig' in your service manager config. This name is
     * reserved to hold the array from application.config.php.
     *
     * The following services can only be overridden from application.config.php:
     *
     * - ModuleManager
     * - SharedEventManager
     * - EventManager & Laminas\EventManager\EventManagerInterface
     *
     * All other services are configured after module loading, thus can be
     * overridden by modules.
     *
     * @return Application
     */
    public static function init(array $configuration = [])
    {
        // Prepare the service manager
        $sm_config = $configuration['service_manager'] ?? [];
        $sm_config = new Service_Manager_Config($sm_config);
        $service_manager = new Service_Manager();
        $sm_config->configure_service_manager($service_manager);
        $service_manager->set_service('ApplicationConfig', $configuration);
        // Load modules
        $service_manager->get('ModuleManager')->load_modules();
        // Prepare list of listeners to bootstrap
        $listeners_from_app_config = $configuration['listeners'] ?? [];
        $config = $service_manager->get('config');
        $listeners_from_config_service = $config['listeners'] ?? [];
        $listeners = array_unique(array_merge($listeners_from_config_service, $listeners_from_app_config));
        return $service_manager->get('Application')->bootstrap($listeners);
    }
    /**
     * Run the application
     *
     * @triggers route(MvcEvent)
     *           Routes the request, and sets the RouteMatch object in the event.
     * @triggers dispatch(MvcEvent)
     *           Dispatches a request, using the discovered RouteMatch and
     *           provided request.
     * @triggers dispatch.error(MvcEvent)
     *           On errors (controller not found, action not supported, etc.),
     *           populates the event with information about the error type,
     *           discovered controller, and controller class (if known).
     *           Typically, a handler should return a populated Response object
     *           that can be returned immediately.
     * @return self
     */
    public function run()
    {
        $events = $this->events;
        $event = $this->event;
        // Define callback used to determine whether or not to short-circuit
        $short_circuit = static function ($r) use ($event): bool {
            if ($r instanceof Response_Interface) {
                return true;
            }
            if ($event->get_error()) {
                return true;
            }
            return false;
        };
        // Trigger route event
        $event->set_name(Mvc_Event::EVENT_ROUTE);
        $event->stop_propagation(false);
        // Clear before triggering
        $result = $events->trigger_event_until($short_circuit, $event);
        if ($result->stopped()) {
            $response = $result->last();
            if ($response instanceof Response_Interface) {
                $event->set_name(Mvc_Event::EVENT_FINISH);
                $event->set_target($this);
                $event->set_response($response);
                $event->stop_propagation(false);
                // Clear before triggering
                $events->trigger_event($event);
                $this->response = $response;
                return $this;
            }
        }
        if ($event->get_error()) {
            return $this->complete_request($event);
        }
        // Trigger dispatch event
        $event->set_name(Mvc_Event::EVENT_DISPATCH);
        $event->stop_propagation(false);
        // Clear before triggering
        $result = $events->trigger_event_until($short_circuit, $event);
        // Complete response
        $response = $result->last();
        if ($response instanceof Response_Interface) {
            $event->set_name(Mvc_Event::EVENT_FINISH);
            $event->set_target($this);
            $event->set_response($response);
            $event->stop_propagation(false);
            // Clear before triggering
            $events->trigger_event($event);
            $this->response = $response;
            return $this;
        }
        $response = $this->response;
        $event->set_response($response);
        return $this->complete_request($event);
    }
    /**
     * Complete the request
     *
     * Triggers "render" and "finish" events, and returns response from
     * event object.
     */
    protected function complete_request(Mvc_Event $event): static
    {
        $events = $this->events;
        $event->set_target($this);
        $event->set_name(Mvc_Event::EVENT_RENDER);
        $event->stop_propagation(false);
        // Clear before triggering
        $events->trigger_event($event);
        $event->set_name(Mvc_Event::EVENT_FINISH);
        $event->stop_propagation(false);
        // Clear before triggering
        $events->trigger_event($event);
        return $this;
    }
}