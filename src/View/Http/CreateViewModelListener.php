<?php

declare (strict_types=1);
namespace Laminas\Mvc\View\Http;

use Laminas\Event_Manager\Abstract_Listener_Aggregate;
use Laminas\Event_Manager\Event_Manager_Interface as Events;
use Laminas\Mvc\Mvc_Event;
use Laminas\Stdlib\Array_Utils;
use Laminas\View\Model\View_Model;
class Create_View_Model_Listener extends Abstract_Listener_Aggregate
{
    /**
     * {@inheritDoc}
     */
    public function attach(Events $events, $priority = 1): void
    {
        $this->listeners[] = $events->attach(Mvc_Event::EVENT_DISPATCH, $this->create_view_model_from_array(...), -80);
        $this->listeners[] = $events->attach(Mvc_Event::EVENT_DISPATCH, $this->create_view_model_from_null(...), -80);
    }
    /**
     * Inspect the result, and cast it to a ViewModel if an assoc array is detected
     */
    public function create_view_model_from_array(Mvc_Event $e): void
    {
        $result = $e->get_result();
        if (!Array_Utils::has_string_keys($result, true)) {
            return;
        }
        $model = new View_Model($result);
        $e->set_result($model);
    }
    /**
     * Inspect the result, and cast it to a ViewModel if null is detected
     */
    public function create_view_model_from_null(Mvc_Event $e): void
    {
        $result = $e->get_result();
        if (null !== $result) {
            return;
        }
        $model = new View_Model();
        $e->set_result($model);
    }
}