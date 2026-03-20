<?php

declare (strict_types=1);
namespace Laminas\Mvc\Controller\Plugin;

use Laminas\Mvc\Exception;
use Laminas\Mvc\Exception\DomainException;
use Laminas\Mvc\Inject_Application_Event_Interface;
use Laminas\Mvc\Mvc_Event;
use Laminas\View\Model\Model_Interface as Model;
class Layout extends Abstract_Plugin
{
    /** @var MvcEvent */
    protected $event;
    /**
     * Set the layout template
     *
     * @param  string $template
     */
    public function set_template($template): static
    {
        $view_model = $this->get_view_model();
        $view_model->set_template((string) $template);
        return $this;
    }
    /**
     * Invoke as a functor
     *
     * If no arguments are given, grabs the "root" or "layout" view model.
     * Otherwise, attempts to set the template for that view model.
     *
     * @param  null|string $template
     * @return Model|Layout
     */
    public function __invoke($template = null)
    {
        if (null === $template) {
            return $this->get_view_model();
        }
        return $this->set_template($template);
    }
    /**
     * Get the event
     *
     * @return MvcEvent
     * @throws Exception\DomainException If unable to find event.
     */
    protected function get_event()
    {
        if ($this->event) {
            return $this->event;
        }
        $controller = $this->get_controller();
        if (!$controller instanceof Inject_Application_Event_Interface) {
            throw new DomainException('Layout plugin requires a controller that implements InjectApplicationEventInterface');
        }
        $event = $controller->get_event();
        if (!$event instanceof Mvc_Event) {
            $params = $event->get_params();
            $event = new Mvc_Event();
            $event->set_params($params);
        }
        $this->event = $event;
        return $this->event;
    }
    /**
     * Retrieve the root view model from the event
     *
     * @return Model
     * @throws Exception\DomainException
     */
    protected function get_view_model()
    {
        $event = $this->get_event();
        $view_model = $event->get_view_model();
        if (!$view_model instanceof Model) {
            throw new DomainException('Layout plugin requires that event view model is populated');
        }
        return $view_model;
    }
}