<?php

declare (strict_types=1);
namespace Laminas\Mvc\Service;

// phpcs:ignore
use function get_debug_type;
use function gettype;
use Interop\Container\Container_Interface;
use function is_array;
use function is_string;
use Laminas\Module_Manager\Listener\Service_Listener;
use Laminas\Module_Manager\Listener\Service_Listener_Interface;
use Laminas\Mvc;
use Laminas\Service_Manager\Exception\Service_Not_Created_Exception;
use Laminas\Service_Manager\Factory\Factory_Interface;
use Laminas\Service_Manager\Factory\Invokable_Factory;
use Laminas\View;
use function sprintf;
class Service_Listener_Factory implements Factory_Interface
{
    /** @var string */
    public const MISSING_KEY_ERROR = 'Invalid service listener options detected, %s array must contain %s key.';
    /** @var string */
    public const VALUE_TYPE_ERROR = 'Invalid service listener options detected, %s must be a string, %s given.';
    /**
     * Default mvc-related service configuration -- can be overridden by modules.
     *
     * @var array
     */
    protected $default_service_config = ['aliases' => ['application' => 'Application', 'Config' => 'config', 'configuration' => 'config', 'Configuration' => 'config', 'HttpDefaultRenderingStrategy' => Mvc\View\Http\Default_Rendering_Strategy::class, 'MiddlewareListener' => Mvc\Middleware_Listener::class, 'request' => 'Request', 'response' => 'Response', 'RouteListener' => Mvc\Route_Listener::class, 'SendResponseListener' => Mvc\Send_Response_Listener::class, 'View' => View\View::class, 'ViewFeedRenderer' => View\Renderer\Feed_Renderer::class, 'ViewJsonRenderer' => View\Renderer\Json_Renderer::class, 'ViewPhpRendererStrategy' => View\Strategy\Php_Renderer_Strategy::class, 'ViewPhpRenderer' => View\Renderer\Php_Renderer::class, 'ViewRenderer' => View\Renderer\Php_Renderer::class, Mvc\Controller\Plugin_Manager::class => 'ControllerPluginManager', Mvc\View\Http\Inject_Template_Listener::class => 'InjectTemplateListener', View\Renderer\Renderer_Interface::class => View\Renderer\Php_Renderer::class, View\Resolver\Template_Map_Resolver::class => 'ViewTemplateMapResolver', View\Resolver\Template_Path_Stack::class => 'ViewTemplatePathStack', View\Resolver\Aggregate_Resolver::class => 'ViewResolver', View\Resolver\Resolver_Interface::class => 'ViewResolver', Mvc\Controller\Controller_Manager::class => 'ControllerManager'], 'invokables' => [], 'factories' => ['Application' => Application_Factory::class, 'config' => Mvc\Service\Config_Factory::class, 'ControllerManager' => Mvc\Service\Controller_Manager_Factory::class, 'ControllerPluginManager' => Mvc\Service\Controller_Plugin_Manager_Factory::class, 'DispatchListener' => Mvc\Service\Dispatch_Listener_Factory::class, 'HttpExceptionStrategy' => Http_Exception_Strategy_Factory::class, 'HttpMethodListener' => Mvc\Service\Http_Method_Listener_Factory::class, 'HttpRouteNotFoundStrategy' => Http_Route_Not_Found_Strategy_Factory::class, 'HttpViewManager' => Mvc\Service\Http_View_Manager_Factory::class, 'InjectTemplateListener' => Mvc\Service\Inject_Template_Listener_Factory::class, 'PaginatorPluginManager' => Mvc\Service\Paginator_Plugin_Manager_Factory::class, 'Request' => Mvc\Service\Request_Factory::class, 'Response' => Mvc\Service\Response_Factory::class, 'ViewHelperManager' => Mvc\Service\View_Helper_Manager_Factory::class, Mvc\View\Http\Default_Rendering_Strategy::class => Http_Default_Rendering_Strategy_Factory::class, 'ViewFeedStrategy' => Mvc\Service\View_Feed_Strategy_Factory::class, 'ViewJsonStrategy' => Mvc\Service\View_Json_Strategy_Factory::class, 'ViewManager' => Mvc\Service\View_Manager_Factory::class, 'ViewResolver' => Mvc\Service\View_Resolver_Factory::class, 'ViewTemplateMapResolver' => Mvc\Service\View_Template_Map_Resolver_Factory::class, 'ViewTemplatePathStack' => Mvc\Service\View_Template_Path_Stack_Factory::class, 'ViewPrefixPathStackResolver' => Mvc\Service\View_Prefix_Path_Stack_Resolver_Factory::class, Mvc\Middleware_Listener::class => Invokable_Factory::class, Mvc\Route_Listener::class => Invokable_Factory::class, Mvc\Send_Response_Listener::class => Send_Response_Listener_Factory::class, View\Renderer\Feed_Renderer::class => Invokable_Factory::class, View\Renderer\Json_Renderer::class => Invokable_Factory::class, View\Renderer\Php_Renderer::class => View_Php_Renderer_Factory::class, View\Strategy\Php_Renderer_Strategy::class => View_Php_Renderer_Strategy_Factory::class, View\View::class => View_Factory::class]];
    /**
     * Create the service listener service
     *
     * Tries to get a service named ServiceListenerInterface from the service
     * locator, otherwise creates a ServiceListener instance, passing it the
     * container instance and the default service configuration, which can be
     * overridden by modules.
     *
     * It looks for the 'service_listener_options' key in the application
     * config and tries to add service/plugin managers as configured. The value
     * of 'service_listener_options' must be a list (array) which contains the
     * following keys:
     *
     * - service_manager: the name of the service manage to create as string
     * - config_key: the name of the configuration key to search for as string
     * - interface: the name of the interface that modules can implement as string
     * - method: the name of the method that modules have to implement as string
     *
     * @param  string              $requestedName
     * @return ServiceListenerInterface
     * @throws ServiceNotCreatedException For invalid ServiceListener service.
     * @throws ServiceNotCreatedException For invalid configurations.
     */
    public function __invoke(Container_Interface $container, $requested_name, ?array $options = null)
    {
        $configuration = $container->get('ApplicationConfig');
        $service_listener = $container->has('ServiceListenerInterface') ? $container->get('ServiceListenerInterface') : new Service_Listener($container);
        if (!$service_listener instanceof Service_Listener_Interface) {
            throw new Service_Not_Created_Exception('The service named ServiceListenerInterface must implement ' . Service_Listener_Interface::class);
        }
        $service_listener->set_default_service_config($this->default_service_config);
        if (isset($configuration['service_listener_options'])) {
            $this->inject_service_listener_options($configuration['service_listener_options'], $service_listener);
        }
        return $service_listener;
    }
    /**
     * Validate and inject plugin manager options into the service listener.
     *
     * @param array $options
     * @throws ServiceListenerInterface For invalid $options types.
     */
    private function inject_service_listener_options($options, Service_Listener_Interface $service_listener): void
    {
        if (!is_array($options)) {
            throw new Service_Not_Created_Exception(sprintf('The value of service_listener_options must be an array, %s given.', get_debug_type($options)));
        }
        foreach ($options as $key => $new_service_manager) {
            $this->validate_plugin_manager_options($new_service_manager, $key);
            $service_listener->add_service_manager($new_service_manager['service_manager'], $new_service_manager['config_key'], $new_service_manager['interface'], $new_service_manager['method']);
        }
    }
    /**
     * Validate the structure and types for plugin manager configuration options.
     *
     * Ensures all required keys are present in the expected types.
     *
     * @param array $options
     * @param string $name Plugin manager service name; used for exception messages
     * @throws ServiceNotCreatedException For any missing configuration options.
     * @throws ServiceNotCreatedException For configuration options of invalid types.
     */
    private function validate_plugin_manager_options($options, int|string $name): void
    {
        if (!is_array($options)) {
            throw new Service_Not_Created_Exception(sprintf('Plugin manager configuration for "%s" is invalid; must be an array, received "%s"', $name, get_debug_type($options)));
        }
        if (!isset($options['service_manager'])) {
            throw new Service_Not_Created_Exception(sprintf(self::MISSING_KEY_ERROR, $name, 'service_manager'));
        }
        if (!is_string($options['service_manager'])) {
            throw new Service_Not_Created_Exception(sprintf(self::VALUE_TYPE_ERROR, 'service_manager', gettype($options['service_manager'])));
        }
        if (!isset($options['config_key'])) {
            throw new Service_Not_Created_Exception(sprintf(self::MISSING_KEY_ERROR, $name, 'config_key'));
        }
        if (!is_string($options['config_key'])) {
            throw new Service_Not_Created_Exception(sprintf(self::VALUE_TYPE_ERROR, 'config_key', gettype($options['config_key'])));
        }
        if (!isset($options['interface'])) {
            throw new Service_Not_Created_Exception(sprintf(self::MISSING_KEY_ERROR, $name, 'interface'));
        }
        if (!is_string($options['interface'])) {
            throw new Service_Not_Created_Exception(sprintf(self::VALUE_TYPE_ERROR, 'interface', gettype($options['interface'])));
        }
        if (!isset($options['method'])) {
            throw new Service_Not_Created_Exception(sprintf(self::MISSING_KEY_ERROR, $name, 'method'));
        }
        if (!is_string($options['method'])) {
            throw new Service_Not_Created_Exception(sprintf(self::VALUE_TYPE_ERROR, 'method', gettype($options['method'])));
        }
    }
}