<?php

declare (strict_types=1);
namespace Laminas\Mvc\Controller;

use function get_debug_type;
use function is_object;
use Laminas\Mvc\Controller\Plugin\Acceptable_View_Model_Selector;
use Laminas\Mvc\Controller\Plugin\Create_Http_Not_Found_Model;
use Laminas\Mvc\Controller\Plugin\Forward;
use Laminas\Mvc\Controller\Plugin\Layout;
use Laminas\Mvc\Controller\Plugin\Params;
use Laminas\Mvc\Controller\Plugin\Plugin_Interface;
use Laminas\Mvc\Controller\Plugin\Redirect;
use Laminas\Mvc\Controller\Plugin\Service\Forward_Factory;
use Laminas\Mvc\Controller\Plugin\Url;
use Laminas\Service_Manager\Abstract_Plugin_Manager;
use Laminas\Service_Manager\Exception\Invalid_Service_Exception;
use Laminas\Service_Manager\Factory\Invokable_Factory;
use Laminas\Stdlib\Dispatchable_Interface;
use function method_exists;
use function sprintf;
/**
 * Plugin manager implementation for controllers
 *
 * Registers a number of default plugins, and contains an initializer for
 * injecting plugins with the current controller.
 */
class Plugin_Manager extends Abstract_Plugin_Manager
{
    /**
     * Plugins must be of this type.
     *
     * @var string
     */
    protected $instance_of = Plugin_Interface::class;
    /** @var string[] Default aliases */
    protected $aliases = [
        'AcceptableViewModelSelector' => Acceptable_View_Model_Selector::class,
        'acceptableViewModelSelector' => Acceptable_View_Model_Selector::class,
        'acceptableviewmodelselector' => Acceptable_View_Model_Selector::class,
        'Forward' => Forward::class,
        'forward' => Forward::class,
        'Layout' => Layout::class,
        'layout' => Layout::class,
        'Params' => Params::class,
        'params' => Params::class,
        'Redirect' => Redirect::class,
        'redirect' => Redirect::class,
        'Url' => Url::class,
        'url' => Url::class,
        'CreateHttpNotFoundModel' => Create_Http_Not_Found_Model::class,
        'createHttpNotFoundModel' => Create_Http_Not_Found_Model::class,
        'createhttpnotfoundmodel' => Create_Http_Not_Found_Model::class,
        // Legacy Zend Framework aliases
        \Zend\Mvc\Controller\Plugin\Forward::class => Forward::class,
        \Zend\Mvc\Controller\Plugin\Acceptable_View_Model_Selector::class => Acceptable_View_Model_Selector::class,
        \Zend\Mvc\Controller\Plugin\Layout::class => Layout::class,
        \Zend\Mvc\Controller\Plugin\Params::class => Params::class,
        \Zend\Mvc\Controller\Plugin\Redirect::class => Redirect::class,
        \Zend\Mvc\Controller\Plugin\Url::class => Url::class,
        \Zend\Mvc\Controller\Plugin\Create_Http_Not_Found_Model::class => Create_Http_Not_Found_Model::class,
        // v2 normalized FQCNs
        'zendmvccontrollerpluginforward' => Forward::class,
        'zendmvccontrollerpluginacceptableviewmodelselector' => Acceptable_View_Model_Selector::class,
        'zendmvccontrollerpluginlayout' => Layout::class,
        'zendmvccontrollerpluginparams' => Params::class,
        'zendmvccontrollerpluginredirect' => Redirect::class,
        'zendmvccontrollerpluginurl' => Url::class,
        'zendmvccontrollerplugincreatehttpnotfoundmodel' => Create_Http_Not_Found_Model::class,
    ];
    /** @var string[]|callable[] Default factories */
    protected $factories = [
        Forward::class => Forward_Factory::class,
        Acceptable_View_Model_Selector::class => Invokable_Factory::class,
        Layout::class => Invokable_Factory::class,
        Params::class => Invokable_Factory::class,
        Redirect::class => Invokable_Factory::class,
        Url::class => Invokable_Factory::class,
        Create_Http_Not_Found_Model::class => Invokable_Factory::class,
        // v2 normalized names
        'laminasmvccontrollerpluginforward' => Forward_Factory::class,
        'laminasmvccontrollerpluginacceptableviewmodelselector' => Invokable_Factory::class,
        'laminasmvccontrollerpluginlayout' => Invokable_Factory::class,
        'laminasmvccontrollerpluginparams' => Invokable_Factory::class,
        'laminasmvccontrollerpluginredirect' => Invokable_Factory::class,
        'laminasmvccontrollerpluginurl' => Invokable_Factory::class,
        'laminasmvccontrollerplugincreatehttpnotfoundmodel' => Invokable_Factory::class,
    ];
    /** @var DispatchableInterface */
    protected $controller;
    /**
     * Retrieve a registered instance
     *
     * After the plugin is retrieved from the service locator, inject the
     * controller in the plugin every time it is requested. This is required
     * because a controller can use a plugin and another controller can be
     * dispatched afterwards. If this second controller uses the same plugin
     * as the first controller, the reference to the controller inside the
     * plugin is lost.
     *
     * @param  string     $name
     * @param  null|array $options Options to use when creating the instance.
     * @return DispatchableInterface
     */
    public function get($name, ?array $options = null)
    {
        $plugin = parent::get($name, $options);
        $this->inject_controller($plugin);
        return $plugin;
    }
    /**
     * Set controller
     *
     * @return PluginManager
     */
    public function set_controller(Dispatchable_Interface $controller)
    {
        $this->controller = $controller;
        return $this;
    }
    /**
     * Retrieve controller instance
     *
     * @return null|DispatchableInterface
     */
    public function get_controller()
    {
        return $this->controller;
    }
    /**
     * Inject a helper instance with the registered controller
     *
     * @param  object $plugin
     */
    public function inject_controller($plugin): void
    {
        if (!is_object($plugin)) {
            return;
        }
        if (!method_exists($plugin, 'setController')) {
            return;
        }
        $controller = $this->get_controller();
        if (!$controller instanceof Dispatchable_Interface) {
            return;
        }
        $plugin->set_controller($controller);
    }
    /**
     * Validate a plugin
     *
     * {@inheritDoc}
     */
    public function validate($plugin): void
    {
        if (!$plugin instanceof $this->instance_of) {
            throw new Invalid_Service_Exception(sprintf('Plugin of type "%s" is invalid; must implement %s', get_debug_type($plugin), $this->instance_of));
        }
    }
}