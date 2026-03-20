<?php

declare (strict_types=1);
namespace Laminas\Mvc\View\Http;

use ArrayAccess;
use function is_array;
use function is_string;
use Laminas\Event_Manager\Abstract_Listener_Aggregate;
use Laminas\Event_Manager\Event_Manager_Interface;
use Laminas\Event_Manager\Listener_Aggregate_Interface;
use Laminas\Mvc\Mvc_Event;
use Laminas\Service_Manager\Service_Manager;
use Laminas\Stdlib\Dispatchable_Interface;
use Laminas\View\Model\Model_Interface;
use Laminas\View\View;
use Traversable;
/**
 * Prepares the view layer
 *
 * Instantiates and configures all classes related to the view layer, including
 * the renderer (and its associated resolver(s) and helper manager), the view
 * object (and its associated rendering strategies), and the various MVC
 * strategies and listeners.
 *
 * Defines and manages the following services:
 *
 * - ViewHelperManager (also aliased to Laminas\View\HelperPluginManager)
 * - ViewTemplateMapResolver (also aliased to Laminas\View\Resolver\TemplateMapResolver)
 * - ViewTemplatePathStack (also aliased to Laminas\View\Resolver\TemplatePathStack)
 * - ViewResolver (also aliased to Laminas\View\Resolver\AggregateResolver and ResolverInterface)
 * - ViewRenderer (also aliased to Laminas\View\Renderer\PhpRenderer and RendererInterface)
 * - ViewPhpRendererStrategy (also aliased to Laminas\View\Strategy\PhpRendererStrategy)
 * - View (also aliased to Laminas\View\View)
 * - DefaultRenderingStrategy (also aliased to Laminas\Mvc\View\Http\DefaultRenderingStrategy)
 * - ExceptionStrategy (also aliased to Laminas\Mvc\View\Http\ExceptionStrategy)
 * - RouteNotFoundStrategy (also aliased to Laminas\Mvc\View\Http\RouteNotFoundStrategy and 404Strategy)
 * - ViewModel
 */
class View_Manager extends Abstract_Listener_Aggregate
{
    /** @var object application configuration service */
    protected $config;
    /** @var MvcEvent */
    protected $event;
    /** @var ServiceManager */
    protected $services;
    /**
     * Various properties representing strategies and objects instantiated and
     * configured by the view manager
     *
     * @var mixed
     */
    protected $helper_manager;
    /** @var mixed */
    protected $mvc_rendering_strategy;
    /** @var mixed */
    protected $renderer;
    /** @var mixed */
    protected $renderer_strategy;
    /** @var mixed */
    protected $resolver;
    /** @var mixed */
    protected $view;
    /** @var mixed */
    protected $view_model;
    /**
     * {@inheritDoc}
     */
    public function attach(Event_Manager_Interface $events, $priority = 1): void
    {
        $this->listeners[] = $events->attach(Mvc_Event::EVENT_BOOTSTRAP, $this->on_bootstrap(...), 10000);
    }
    /**
     * Prepares the view layer
     *
     * @param MvcEvent $event
     */
    public function on_bootstrap($event): void
    {
        $application = $event->get_application();
        $services = $application->get_service_manager();
        $config = $services->get('config');
        $events = $application->get_event_manager();
        $shared_events = $events->get_shared_manager();
        $this->config = isset($config['view_manager']) && (is_array($config['view_manager']) || $config['view_manager'] instanceof ArrayAccess) ? $config['view_manager'] : [];
        $this->services = $services;
        $this->event = $event;
        $route_not_found_strategy = $services->get('HttpRouteNotFoundStrategy');
        $exception_strategy = $services->get('HttpExceptionStrategy');
        $mvc_rendering_strategy = $services->get('HttpDefaultRenderingStrategy');
        $this->inject_view_model_into_plugin();
        $inject_template_listener = $services->get(Inject_Template_Listener::class);
        $create_view_model_listener = new Create_View_Model_Listener();
        $inject_view_model_listener = new Inject_View_Model_Listener();
        $this->register_mvc_rendering_strategies($events);
        $this->register_view_strategies();
        $route_not_found_strategy->attach($events);
        $exception_strategy->attach($events);
        $events->attach(Mvc_Event::EVENT_DISPATCH_ERROR, $inject_view_model_listener->inject_view_model(...), -100);
        $events->attach(Mvc_Event::EVENT_RENDER_ERROR, $inject_view_model_listener->inject_view_model(...), -100);
        $mvc_rendering_strategy->attach($events);
        $shared_events->attach(Dispatchable_Interface::class, Mvc_Event::EVENT_DISPATCH, $create_view_model_listener->create_view_model_from_array(...), -80);
        $shared_events->attach(Dispatchable_Interface::class, Mvc_Event::EVENT_DISPATCH, [$route_not_found_strategy, 'prepareNotFoundViewModel'], -90);
        $shared_events->attach(Dispatchable_Interface::class, Mvc_Event::EVENT_DISPATCH, $create_view_model_listener->create_view_model_from_null(...), -80);
        $shared_events->attach(Dispatchable_Interface::class, Mvc_Event::EVENT_DISPATCH, [$inject_template_listener, 'injectTemplate'], -90);
        $shared_events->attach(Dispatchable_Interface::class, Mvc_Event::EVENT_DISPATCH, $inject_view_model_listener->inject_view_model(...), -100);
    }
    /**
     * Retrieves the View instance
     *
     * @return View
     */
    public function get_view()
    {
        if ($this->view) {
            return $this->view;
        }
        $this->view = $this->services->get(View::class);
        return $this->view;
    }
    /**
     * Configures the MvcEvent view model to ensure it has the template injected
     *
     * @return ModelInterface
     */
    public function get_view_model()
    {
        if ($this->view_model) {
            return $this->view_model;
        }
        $this->view_model = $model = $this->event->get_view_model();
        $layout_template = $this->services->get('HttpDefaultRenderingStrategy')->get_layout_template();
        $model->set_template($layout_template);
        return $this->view_model;
    }
    /**
     * Register additional mvc rendering strategies
     *
     * If there is a "mvc_strategies" key of the view manager configuration, loop
     * through it. Pull each as a service from the service manager, and, if it
     * is a ListenerAggregate, attach it to the view, at priority 100. This
     * latter allows each to trigger before the default mvc rendering strategy,
     * and for them to trigger in the order they are registered.
     *
     * @return void
     */
    protected function register_mvc_rendering_strategies(Event_Manager_Interface $events)
    {
        if (!isset($this->config['mvc_strategies'])) {
            return;
        }
        $mvc_strategies = $this->config['mvc_strategies'];
        if (is_string($mvc_strategies)) {
            $mvc_strategies = [$mvc_strategies];
        }
        if (!is_array($mvc_strategies) && !$mvc_strategies instanceof Traversable) {
            return;
        }
        foreach ($mvc_strategies as $mvc_strategy) {
            if (!is_string($mvc_strategy)) {
                continue;
            }
            $listener = $this->services->get($mvc_strategy);
            if ($listener instanceof Listener_Aggregate_Interface) {
                $listener->attach($events, 100);
            }
        }
    }
    /**
     * Register additional view strategies
     *
     * If there is a "strategies" key of the view manager configuration, loop
     * through it. Pull each as a service from the service manager, and, if it
     * is a ListenerAggregate, attach it to the view, at priority 100. This
     * latter allows each to trigger before the default strategy, and for them
     * to trigger in the order they are registered.
     *
     * @return void
     */
    protected function register_view_strategies()
    {
        if (!isset($this->config['strategies'])) {
            return;
        }
        $strategies = $this->config['strategies'];
        if (is_string($strategies)) {
            $strategies = [$strategies];
        }
        if (!is_array($strategies) && !$strategies instanceof Traversable) {
            return;
        }
        $view = $this->get_view();
        $events = $view->get_event_manager();
        foreach ($strategies as $strategy) {
            if (!is_string($strategy)) {
                continue;
            }
            $listener = $this->services->get($strategy);
            if ($listener instanceof Listener_Aggregate_Interface) {
                $listener->attach($events, 100);
            }
        }
    }
    /**
     * Injects the ViewModel view helper with the root view model.
     */
    private function inject_view_model_into_plugin(): void
    {
        $model = $this->get_view_model();
        $plugins = $this->services->get('ViewHelperManager');
        $plugin = $plugins->get('viewmodel');
        $plugin->set_root($model);
    }
}