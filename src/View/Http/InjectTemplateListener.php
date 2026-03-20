<?php

declare (strict_types=1);
namespace Laminas\Mvc\View\Http;

use function array_diff;
use function array_pop;
use function explode;
use function implode;
use function is_object;
use function is_string;
use function krsort;
use Laminas\Event_Manager\Abstract_Listener_Aggregate;
use Laminas\Event_Manager\Event_Manager_Interface as Events;
use Laminas\Mvc\Mvc_Event;
use Laminas\Stdlib\String_Utils;
use Laminas\View\Model\Model_Interface as ViewModel;
use function preg_replace;
use function rtrim;
use function str_contains;
use function str_ends_with;
use function str_starts_with;
use function strlen;
use function strrpos;
use function strtolower;
use function substr;
use function trim;
class Inject_Template_Listener extends Abstract_Listener_Aggregate
{
    /**
     * Array of controller namespace -> template mappings
     *
     * @var array
     */
    protected $controller_map = [];
    /**
     * Flag to force the use of the route match controller param
     *
     * @var boolean
     */
    protected $prefer_route_match_controller = false;
    /**
     * {@inheritDoc}
     */
    public function attach(Events $events, $priority = 1): void
    {
        $this->listeners[] = $events->attach(Mvc_Event::EVENT_DISPATCH, $this->inject_template(...), -90);
    }
    /**
     * Inject a template into the view model, if none present
     *
     * Template is derived from the controller found in the route match, and,
     * optionally, the action, if present.
     */
    public function inject_template(Mvc_Event $e): void
    {
        $model = $e->get_result();
        if (!$model instanceof View_Model) {
            return;
        }
        $template = $model->get_template();
        if (!empty($template)) {
            return;
        }
        $route_match = $e->get_route_match();
        if ($prefer_route_match_controller = $route_match->get_param('prefer_route_match_controller', false)) {
            $this->set_prefer_route_match_controller($prefer_route_match_controller);
        }
        $controller = $e->get_target();
        if (is_object($controller)) {
            $controller = $controller::class;
        }
        $route_match_controller = $route_match->get_param('controller', '');
        if (!$controller || $this->prefer_route_match_controller && $route_match_controller) {
            $controller = $route_match_controller;
        }
        $template = $this->map_controller($controller);
        $action = $route_match->get_param('action');
        if (null !== $action) {
            $template .= '/' . $this->inflect_name($action);
        }
        $model->set_template($template);
    }
    /**
     * Set map of controller namespace -> template pairs
     *
     * @return self
     */
    public function set_controller_map(array $map)
    {
        krsort($map);
        $this->controller_map = $map;
        return $this;
    }
    /**
     * Maps controller to template if controller namespace is whitelisted or mapped
     *
     * @param string $controller controller FQCN
     * @return string|false template name or false if controller was not matched
     */
    public function map_controller($controller)
    {
        $mapped = '';
        foreach ($this->controller_map as $namespace => $replacement) {
            if ($replacement === false) {
                continue;
            }
            if (!($controller === $namespace || str_starts_with($controller, $namespace . '\\'))) {
                continue;
            }
            // Map namespace to $replacement if its value is string
            if (is_string($replacement)) {
                $mapped = rtrim($replacement, '/') . '/';
                $controller = substr($controller, strlen((string) $namespace) + 1) ?: '';
                break;
            }
        }
        //strip Controller namespace(s) (but not classname)
        $parts = explode('\\', $controller);
        array_pop($parts);
        $parts = array_diff($parts, ['Controller']);
        //strip trailing Controller in class name
        $parts[] = $this->derive_controller_class($controller);
        $controller = implode('/', $parts);
        $template = trim($mapped . $controller, '/');
        // inflect CamelCase to dash
        return $this->inflect_name($template);
    }
    /**
     * Inflect a name to a normalized value
     *
     * Inlines the logic from laminas-filter's Word\CamelCaseToDash filter.
     *
     * @param  string $name
     * @return string
     */
    protected function inflect_name($name)
    {
        if (String_Utils::has_pcre_unicode_support()) {
            $pattern = ['#(?<=(?:\p{Lu}))(\p{Lu}\p{Ll})#', '#(?<=(?:\p{Ll}|\p{Nd}))(\p{Lu})#'];
            $replacement = ['-\1', '-\1'];
        } else {
            $pattern = ['#(?<=(?:[A-Z]))([A-Z]+)([A-Z][a-z])#', '#(?<=(?:[a-z0-9]))([A-Z])#'];
            $replacement = ['\1-\2', '-\1'];
        }
        $name = preg_replace($pattern, $replacement, $name);
        return strtolower((string) $name);
    }
    /**
     * Determine the name of the controller
     *
     * Strip the namespace, and the suffix "Controller" if present.
     *
     * @param  string $controller
     * @return string
     */
    protected function derive_controller_class($controller)
    {
        if (str_contains($controller, '\\')) {
            $controller = substr($controller, strrpos($controller, '\\') + 1);
        }
        if (10 < strlen($controller) && str_ends_with($controller, 'Controller')) {
            return substr($controller, 0, -10);
        }
        return $controller;
    }
    /**
     * Sets the flag to instruct the listener to prefer the route match controller param
     * over the class name
     *
     * @param boolean $preferRouteMatchController
     */
    public function set_prefer_route_match_controller($prefer_route_match_controller): void
    {
        $this->prefer_route_match_controller = (bool) $prefer_route_match_controller;
    }
    /**
     * @return boolean
     */
    public function is_prefer_route_match_controller()
    {
        return $this->prefer_route_match_controller;
    }
}