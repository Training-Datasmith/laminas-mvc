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
     * Create a new Application instance.
     *
     * All dependencies except the service manager are optional — when null,
     * they are resolved from the service manager using their standard service names.
     *
     * @param Service_Manager              $service_manager The configured IoC container
     * @param Event_Manager_Interface|null $events          Optional pre-built event manager;
     *                                                       defaults to 'EventManager' from container
     * @param Request_Interface|null       $request         Optional pre-built request;
     *                                                       defaults to 'Request' from container
     * @param Response_Interface|null      $response        Optional pre-built response;
     *                                                       defaults to 'Response' from container
     * @since 3.0.0
     */
    public function __construct(protected Service_Manager $service_manager, ?Event_Manager_Interface $events = null, ?Request_Interface $request = null, ?Response_Interface $response = null)
    {
        $this->set_event_manager($events ?: $service_manager->get('EventManager'));
        $this->request = $request ?: $service_manager->get('Request');
        $this->response = $response ?: $service_manager->get('Response');
    }
    /**
     * Retrieve the merged application configuration array.
     *
     * Returns the 'config' service from the container, which is typically the
     * merged result of all module configurations.
     *
     * @return array<mixed>|object The merged application configuration
     * @since 3.0.0
     */
    public function get_config(): array|object
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
     * Return the current request object.
     *
     * @return Request_Interface The HTTP (or CLI) request for this dispatch cycle
     * @since 3.0.0
     */
    public function get_request(): Request_Interface
    {
        return $this->request;
    }
    /**
     * Return the current response object.
     *
     * The response is mutable; listeners and controllers write to it during
     * the dispatch lifecycle.
     *
     * @return Response_Interface The response for this dispatch cycle
     * @since 3.0.0
     */
    public function get_response(): Response_Interface
    {
        return $this->response;
    }
    /**
     * Return the MVC event that carries state through the dispatch lifecycle.
     *
     * The event is populated with the request, response, router, and route match
     * during bootstrap() and modified as each lifecycle stage completes.
     *
     * @return Mvc_Event The shared MVC lifecycle event
     * @since 3.0.0
     */
    public function get_mvc_event(): Mvc_Event
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
     * Retrieve the event manager.
     *
     * @return Event_Manager_Interface The application's event manager
     * @since 3.0.0
     */
    public function get_event_manager(): Event_Manager_Interface
    {
        return $this->events;
    }
    /**
     * Bootstrap and return a fully-initialised Application from a configuration array.
     *
     * This is the standard entry point for a Laminas MVC application. It:
     * 1. Builds and configures a ServiceManager from `$configuration['service_manager']`
     * 2. Registers the full `$configuration` array as 'ApplicationConfig'
     * 3. Loads all configured modules via ModuleManager
     * 4. Bootstraps the Application with merged listeners from config and app config
     *
     * Note: 'ApplicationConfig' is a reserved service name — do not register a
     * custom service with that name in your service manager configuration.
     * The following services may only be overridden from application.config.php:
     * - ModuleManager, SharedEventManager, EventManager
     *
     * @param array<string, mixed> $configuration The full application configuration array
     * @return static The bootstrapped, ready-to-run Application
     * @since 3.0.0
     */
    public static function init(array $configuration = []): static
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
     * Execute the full MVC request/response lifecycle.
     *
     * Triggers the following events in sequence:
     *   1. `route`          — routes the request; populates RouteMatch on the event
     *   2. `dispatch`       — dispatches the matched controller action
     *   3. `render`         — renders the view model to a string response (via complete_request)
     *   4. `finish`         — final processing (e.g. SendResponseListener sends headers)
     *
     * If routing or dispatch returns a Response directly, the remaining events are
     * short-circuited and `finish` is triggered immediately.
     *
     * The `dispatch.error` event is triggered when no controller can be found,
     * the controller cannot be dispatched, or any other dispatch-time error occurs.
     *
     * @return static The application instance after the full lifecycle has run
     * @since 3.0.0
     */
    public function run(): static
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