<?php

declare (strict_types=1);
namespace Laminas\Mvc\View\Http;

use Laminas\Event_Manager\Abstract_Listener_Aggregate;
use Laminas\Event_Manager\Event_Manager_Interface;
use Laminas\Http\Response as HttpResponse;
use Laminas\Mvc\Application;
use Laminas\Mvc\Mvc_Event;
use Laminas\Stdlib\Response_Interface as Response;
use Laminas\View\Model\View_Model;
class Exception_Strategy extends Abstract_Listener_Aggregate
{
    /**
     * Display exceptions?
     *
     * @var bool
     */
    protected $display_exceptions = false;
    /**
     * Name of exception template
     *
     * @var string
     */
    protected $exception_template = 'error';
    /**
     * {@inheritDoc}
     */
    public function attach(Event_Manager_Interface $events, $priority = 1): void
    {
        $this->listeners[] = $events->attach(Mvc_Event::EVENT_DISPATCH_ERROR, $this->prepare_exception_view_model(...));
        $this->listeners[] = $events->attach(Mvc_Event::EVENT_RENDER_ERROR, $this->prepare_exception_view_model(...));
    }
    /**
     * Flag: display exceptions in error pages?
     *
     * @param  bool $displayExceptions
     * @return ExceptionStrategy
     */
    public function set_display_exceptions($display_exceptions)
    {
        $this->display_exceptions = (bool) $display_exceptions;
        return $this;
    }
    /**
     * Should we display exceptions in error pages?
     *
     * @return bool
     */
    public function display_exceptions()
    {
        return $this->display_exceptions;
    }
    /**
     * Set the exception template
     *
     * @param  string $exceptionTemplate
     * @return ExceptionStrategy
     */
    public function set_exception_template($exception_template)
    {
        $this->exception_template = (string) $exception_template;
        return $this;
    }
    /**
     * Retrieve the exception template
     *
     * @return string
     */
    public function get_exception_template()
    {
        return $this->exception_template;
    }
    /**
     * Create an exception view model, and set the HTTP status code
     *
     * @todo   dispatch.error does not halt dispatch unless a response is
     *         returned. As such, we likely need to trigger rendering as a low
     *         priority dispatch.error event (or goto a render event) to ensure
     *         rendering occurs, and that munging of view models occurs when
     *         expected.
     */
    public function prepare_exception_view_model(Mvc_Event $e): void
    {
        // Do nothing if no error in the event
        $error = $e->get_error();
        if (empty($error)) {
            return;
        }
        // Do nothing if the result is a response object
        $result = $e->get_result();
        if ($result instanceof Response) {
            return;
        }
        switch ($error) {
            case Application::ERROR_CONTROLLER_NOT_FOUND:
            case Application::ERROR_CONTROLLER_INVALID:
            case Application::ERROR_ROUTER_NO_MATCH:
                // Specifically not handling these
                return;
            case Application::ERROR_EXCEPTION:
            default:
                $model = new View_Model(['message' => 'An error occurred during execution; please try again later.', 'exception' => $e->get_param('exception'), 'display_exceptions' => $this->display_exceptions()]);
                $model->set_template($this->get_exception_template());
                $e->set_result($model);
                $response = $e->get_response();
                if (!$response) {
                    $response = new Http_Response();
                    $response->set_status_code(500);
                    $e->set_response($response);
                } else {
                    $status_code = $response->get_status_code();
                    if ($status_code === 200) {
                        $response->set_status_code(500);
                    }
                }
                break;
        }
    }
}