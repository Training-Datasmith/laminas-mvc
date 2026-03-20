<?php

declare (strict_types=1);
namespace Laminas\Mvc\View\Http;

use Exception;
use Laminas\Event_Manager\Abstract_Listener_Aggregate;
use Laminas\Event_Manager\Event_Manager_Interface;
use Laminas\Mvc\Application;
use Laminas\Mvc\Mvc_Event;
use Laminas\Stdlib\Response_Interface as Response;
use Laminas\View\Model\Model_Interface as ViewModel;
use Laminas\View\View;
use Throwable;
class Default_Rendering_Strategy extends Abstract_Listener_Aggregate
{
    /**
     * Layout template - template used in root ViewModel of MVC event.
     *
     * @var string
     */
    protected $layout_template = 'layout';
    /**
     * Set view
     */
    public function __construct(protected View $view)
    {
    }
    /**
     * {@inheritDoc}
     */
    public function attach(Event_Manager_Interface $events, $priority = 1): void
    {
        $this->listeners[] = $events->attach(Mvc_Event::EVENT_RENDER, $this->render(...), -10000);
        $this->listeners[] = $events->attach(Mvc_Event::EVENT_RENDER_ERROR, $this->render(...), -10000);
    }
    /**
     * Set layout template value
     *
     * @param  string $layoutTemplate
     * @return DefaultRenderingStrategy
     */
    public function set_layout_template($layout_template)
    {
        $this->layout_template = (string) $layout_template;
        return $this;
    }
    /**
     * Get layout template value
     *
     * @return string
     */
    public function get_layout_template()
    {
        return $this->layout_template;
    }
    /**
     * Render the view
     *
     * @return Response|null
     * @throws Exception|Throwable
     */
    public function render(Mvc_Event $e)
    {
        $result = $e->get_result();
        if ($result instanceof Response) {
            return $result;
        }
        // Martial arguments
        $request = $e->get_request();
        $response = $e->get_response();
        $view_model = $e->get_view_model();
        if (!$view_model instanceof View_Model) {
            return;
        }
        $view = $this->view;
        $view->set_request($request);
        $view->set_response($response);
        $caught_exception = null;
        try {
            $view->render($view_model);
        } catch (Throwable $ex) {
            $caught_exception = $ex;
        }
        if ($caught_exception !== null) {
            if ($e->get_name() === Mvc_Event::EVENT_RENDER_ERROR) {
                throw $caught_exception;
            }
            $application = $e->get_application();
            $events = $application->get_event_manager();
            $e->set_error(Application::ERROR_EXCEPTION);
            $e->set_param('exception', $caught_exception);
            $e->set_name(Mvc_Event::EVENT_RENDER_ERROR);
            $events->trigger_event($e);
        }
        return $response;
    }
}