# Architecture: laminas-mvc

## Purpose

laminas-mvc is a full-stack MVC framework layer built on top of Laminas components.
It provides the request/response lifecycle (route → dispatch → render → finish)
driven by an event manager, enabling loosely coupled extension at every stage.

## Directory Structure

```
src/
  Application.php                     # Main entry point; orchestrates the full lifecycle
  Application_Interface.php           # Public contract for the application
  Mvc_Event.php                       # Shared lifecycle event carrying request, response, router, etc.
  Inject_Application_Event_Interface.php  # Marks controllers that receive the MvcEvent
  Dispatch_Listener.php               # Routes dispatch events to controller actions
  Route_Listener.php                  # Handles route events via the router
  Http_Method_Listener.php            # Validates HTTP method constraints
  Middleware_Listener.php             # Dispatches PSR-15 middleware
  Module_Route_Listener.php           # Prefixes routes with module name
  Send_Response_Listener.php          # Sends the response at the finish event
  Controller/
    Abstract_Controller.php           # Base for all controllers; plugin support + event injection
    Abstract_Action_Controller.php    # Adds action-method routing (indexAction, etc.)
    Abstract_Restful_Controller.php   # REST-oriented controller base (GET/POST/PUT/DELETE)
    Controller_Manager.php            # Typed service manager for controller classes
    Middleware_Controller.php         # Wraps a PSR-15 middleware as a controller
    Lazy_Controller_Abstract_Factory.php  # Abstract factory for lazy controller creation
    Plugin/
      Plugin_Interface.php            # Contract for controller plugins
      Abstract_Plugin.php             # Base plugin; provides controller + event access
      Forward.php                     # Forward dispatch to another controller/action
      Redirect.php                    # Generate redirect responses
      Url.php                         # Assemble URLs from route definitions
      Layout.php                      # Access/configure the layout template
      Params.php                      # Typed access to route, query, and body parameters
      Acceptable_View_Model_Selector.php  # Content-negotiation helper
      Create_Http_Not_Found_Model.php # Helper for 404 view model creation
      Plugin_Manager.php              # Service manager for plugins
  Service/
    Application_Factory.php           # Creates the Application service
    Controller_Manager_Factory.php    # Creates the ControllerManager
    [20+ factories]                   # One factory per service registered by the module
  View/
    Http/
      Default_Rendering_Strategy.php  # Renders ViewModels to Response bodies
      Exception_Strategy.php          # Renders exception view models on error
      Route_Not_Found_Strategy.php    # Renders 404 responses
      View_Manager.php                # Wires all view-related listeners
      Inject_Template_Listener.php    # Infers template names from controller/action
  ResponseSender/
    Abstract_Response_Sender.php      # Base for response sending
    Http_Response_Sender.php          # Sends HTTP responses
    Php_Environment_Response_Sender.php  # Sends via PHP's native header()/echo
    Simple_Stream_Response_Sender.php # Streams large response bodies
  Exception/                          # Domain exception hierarchy
```

## Key Design Decisions

- **Event-driven lifecycle**: Every stage (route, dispatch, render, finish) is an
  event. Listeners are attached by the service-registered bootstrap listeners, making
  any stage swappable or extensible without subclassing Application.
- **Plugin system**: Controller plugins (`Forward`, `Redirect`, `Url`, `Params`) are
  first-class objects retrieved from a `Plugin_Manager`. This keeps `Abstract_Controller`
  lean and makes plugins individually testable.
- **No coupling to HTTP**: `Application_Interface` depends on `Request_Interface` and
  `Response_Interface` (Laminas\Stdlib), not on HTTP-specific classes. The HTTP
  concrete classes are wired by the service factories, allowing CLI dispatch.
- **Module integration**: `init()` triggers `ModuleManager::load_modules()` before
  bootstrapping, so modules can contribute services, routes, and listeners via their
  `Module.php` `getConfig()` / `onBootstrap()` methods.
- **ERROR_* constants**: Dispatch errors (controller not found, action not dispatchable)
  are communicated by setting named error constants on `Mvc_Event`, not by throwing
  exceptions, so listeners can render appropriate error views.

## Extension Points

- Listen to `Mvc_Event::EVENT_BOOTSTRAP` to attach your own listeners.
- Implement `Abstract_Action_Controller` for standard action-based controllers.
- Implement `Abstract_Restful_Controller` for RESTful resource controllers.
- Write a controller plugin implementing `Plugin_Interface` for reusable controller helpers.
- Register factories in `Service/` to override any default service.

## Dependency Flow

```
Application::init()
  → Service_Manager_Config::configure_service_manager()
  → ModuleManager::load_modules()
  → Application::bootstrap(listeners)
      → RouteListener, DispatchListener, HttpMethodListener,
        MiddlewareListener, ViewManager, SendResponseListener
  → Application::run()
      → trigger EVENT_ROUTE  → RouteListener resolves RouteMatch
      → trigger EVENT_DISPATCH → DispatchListener → ControllerManager → Controller
      → trigger EVENT_RENDER   → DefaultRenderingStrategy → View → Response body
      → trigger EVENT_FINISH   → SendResponseListener → sends headers + body
```
