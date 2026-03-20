<?php

declare (strict_types=1);
namespace Laminas\Mvc\Controller;

use Laminas\Mvc\Exception;
use Laminas\Mvc\Exception\DomainException;
use Laminas\Mvc\Mvc_Event;
use Laminas\View\Model\View_Model;
use function method_exists;
/**
 * Basic action controller
 */
abstract class Abstract_Action_Controller extends Abstract_Controller
{
    /**
     * {@inheritDoc}
     */
    protected $event_identifier = self::class;
    /**
     * Default action if none provided
     *
     * @return ViewModel
     */
    public function index_action()
    {
        return new View_Model(['content' => 'Placeholder page']);
    }
    /**
     * Action called if matched action does not exist
     *
     * @return ViewModel
     */
    public function not_found_action()
    {
        $event = $this->get_event();
        $route_match = $event->get_route_match();
        $route_match->set_param('action', 'not-found');
        $helper = $this->plugin('createHttpNotFoundModel');
        return $helper($event->get_response());
    }
    /**
     * Execute the request
     *
     * @return mixed
     * @throws Exception\DomainException
     */
    public function on_dispatch(Mvc_Event $e)
    {
        $route_match = $e->get_route_match();
        if (!$route_match) {
            /**
             * @todo Determine requirements for when route match is missing.
             *       Potentially allow pulling directly from request metadata?
             */
            throw new DomainException('Missing route matches; unsure how to retrieve action');
        }
        $action = $route_match->get_param('action', 'not-found');
        $method = static::get_method_from_action($action);
        if (!method_exists($this, $method)) {
            $method = 'not_found_action';
        }
        $action_response = $this->{$method}();
        $e->set_result($action_response);
        return $action_response;
    }
}