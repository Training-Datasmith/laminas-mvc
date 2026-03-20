<?php

declare (strict_types=1);
namespace Laminas\Mvc\View\Http;

use Laminas\Event_Manager\Abstract_Listener_Aggregate;
use Laminas\Event_Manager\Event_Manager_Interface as Events;
use Laminas\Mvc\Mvc_Event;
use Laminas\View\Model\Clearable_Model_Interface;
use Laminas\View\Model\Model_Interface as ViewModel;
class Inject_View_Model_Listener extends Abstract_Listener_Aggregate
{
    /**
     * {@inheritDoc}
     */
    public function attach(Events $events, $priority = 1): void
    {
        $this->listeners[] = $events->attach(Mvc_Event::EVENT_DISPATCH, $this->inject_view_model(...), -100);
        $this->listeners[] = $events->attach(Mvc_Event::EVENT_DISPATCH_ERROR, $this->inject_view_model(...), -100);
        $this->listeners[] = $events->attach(Mvc_Event::EVENT_RENDER_ERROR, $this->inject_view_model(...), -100);
    }
    /**
     * Insert the view model into the event
     *
     * Inspects the MVC result; if it's a view model, it then either (a) adds
     * it as a child to the default, composed view model, or (b) replaces it
     * if the result is marked as terminable.
     */
    public function inject_view_model(Mvc_Event $e): void
    {
        $result = $e->get_result();
        if (!$result instanceof View_Model) {
            return;
        }
        $model = $e->get_view_model();
        if ($result->terminate()) {
            $e->set_view_model($result);
            return;
        }
        if ($e->get_error() && $model instanceof Clearable_Model_Interface) {
            $model->clear_children();
        }
        $model->add_child($result);
    }
}