<?php

declare (strict_types=1);
namespace Laminas\Mvc\View\Http;

use Laminas\Event_Manager\Abstract_Listener_Aggregate;
use Laminas\Event_Manager\Event_Manager_Interface;
use Laminas\Http\Request as HttpRequest;
use Laminas\Mvc\Mvc_Event;
class Inject_Routematch_Params_Listener extends Abstract_Listener_Aggregate
{
    /**
     * Should request params overwrite existing request params?
     *
     * @var bool
     */
    protected $overwrite = true;
    /**
     * {@inheritDoc}
     */
    public function attach(Event_Manager_Interface $events, $priority = 1): void
    {
        $this->listeners[] = $events->attach(Mvc_Event::EVENT_DISPATCH, $this->inject_params(...), 90);
    }
    /**
     * Take parameters from RouteMatch and inject them into the request.
     */
    public function inject_params(Mvc_Event $e): void
    {
        $route_match_params = $e->get_route_match()->get_params();
        $request = $e->get_request();
        if (!$request instanceof Http_Request) {
            // unsupported request type
            return;
        }
        $params = $request->get();
        if ($this->overwrite) {
            // Overwrite existing parameters, or create new ones if not present.
            foreach ($route_match_params as $key => $val) {
                $params->{$key} = $val;
            }
            return;
        }
        // Only create new parameters.
        foreach ($route_match_params as $key => $val) {
            if (!$params->offsetExists($key)) {
                $params->{$key} = $val;
            }
        }
    }
    /**
     * Should RouteMatch parameters replace existing Request params?
     *
     * @param  bool $overwrite
     */
    public function set_overwrite($overwrite): void
    {
        $this->overwrite = $overwrite;
    }
    /**
     * @return bool
     */
    public function get_overwrite()
    {
        return $this->overwrite;
    }
}