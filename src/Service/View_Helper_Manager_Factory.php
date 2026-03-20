<?php

declare (strict_types=1);
namespace Laminas\Mvc\Service;

// phpcs:ignore
use Interop\Container\Container_Interface;
use function is_callable;
use Laminas\Router\Route_Match;
use Laminas\Service_Manager\Exception\Service_Not_Created_Exception;
use Laminas\View\Helper as ViewHelper;
use Laminas\View\Helper\Base_Path;
use Laminas\View\Helper\Doctype;
use Laminas\View\Helper\Url;
use Laminas\View\Helper_Plugin_Manager;
class View_Helper_Manager_Factory extends Abstract_Plugin_Manager_Factory
{
    public const PLUGIN_MANAGER_CLASS = Helper_Plugin_Manager::class;
    /**
     * An array of helper configuration classes to ensure are on the helper_map stack.
     *
     * These are *not* imported; that way they can be optional dependencies.
     *
     * @todo Remove these once their components have Modules defined.
     * @var array
     */
    protected $default_helper_map_classes = [];
    /**
     * Create and return the view helper manager
     *
     * @param  string             $requestedName
     * @return HelperPluginManager
     * @throws ServiceNotCreatedException
     */
    public function __invoke(Container_Interface $container, $requested_name, ?array $options = null)
    {
        $options = $options ?: [];
        $options['factories'] ??= [];
        $plugins = parent::__invoke($container, $requested_name, $options);
        // Override plugin factories
        $plugins = $this->inject_override_factories($plugins, $container);
        return $plugins;
    }
    /**
     * Inject override factories into the plugin manager.
     */
    private function inject_override_factories(Helper_Plugin_Manager $plugins, Container_Interface $services): Helper_Plugin_Manager
    {
        // Configure URL view helper
        $url_factory = $this->create_url_helper_factory($services);
        $plugins->set_factory(View_Helper\Url::class, $url_factory);
        $plugins->set_factory('laminasviewhelperurl', $url_factory);
        // Configure base path helper
        $base_path_factory = $this->create_base_path_helper_factory($services);
        $plugins->set_factory(View_Helper\Base_Path::class, $base_path_factory);
        $plugins->set_factory('laminasviewhelperbasepath', $base_path_factory);
        // Configure doctype view helper
        $doctype_factory = $this->create_doctype_helper_factory($services);
        $plugins->set_factory(View_Helper\Doctype::class, $doctype_factory);
        $plugins->set_factory('laminasviewhelperdoctype', $doctype_factory);
        return $plugins;
    }
    /**
     * Create and return a factory for creating a URL helper.
     *
     * Retrieves the application and router from the servicemanager,
     * and the route match from the MvcEvent composed by the application,
     * using them to configure the helper.
     *
     * @return callable
     */
    private function create_url_helper_factory(Container_Interface $services)
    {
        return static function () use ($services): Url {
            $helper = new View_Helper\Url();
            $helper->set_router($services->get('HttpRouter'));
            $match = $services->get('Application')->get_mvc_event()->get_route_match();
            if ($match instanceof Route_Match) {
                $helper->set_route_match($match);
            }
            return $helper;
        };
    }
    /**
     * Create and return a factory for creating a BasePath helper.
     *
     * Uses configuration and request services to configure the helper.
     *
     * @return callable
     */
    private function create_base_path_helper_factory(Container_Interface $services)
    {
        return static function () use ($services): Base_Path {
            $config = $services->has('config') ? $services->get('config') : [];
            $helper = new View_Helper\Base_Path();
            if (isset($config['view_manager']) && isset($config['view_manager']['base_path'])) {
                $helper->set_base_path($config['view_manager']['base_path']);
                return $helper;
            }
            $request = $services->get('Request');
            if (is_callable([$request, 'getBasePath'])) {
                $helper->set_base_path($request->get_base_path());
            }
            return $helper;
        };
    }
    /**
     * Create and return a Doctype helper factory.
     *
     * Other view helpers depend on this to decide which spec to generate their tags
     * based on. This is why it must be set early instead of later in the layout phtml.
     *
     * @return callable
     */
    private function create_doctype_helper_factory(Container_Interface $services)
    {
        return static function () use ($services): Doctype {
            $config = $services->has('config') ? $services->get('config') : [];
            $config = $config['view_manager'] ?? [];
            $helper = new View_Helper\Doctype();
            if (isset($config['doctype']) && $config['doctype']) {
                $helper->set_doctype($config['doctype']);
            }
            return $helper;
        };
    }
}