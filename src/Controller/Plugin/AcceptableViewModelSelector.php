<?php

declare (strict_types=1);
namespace Laminas\Mvc\Controller\Plugin;

use function class_exists;
use function is_array;
use function key;
use Laminas\Http\Header\Accept;
use Laminas\Http\Header\Accept\Field_Value_Part\Abstract_Field_Value_Part;
use Laminas\Http\Request;
use Laminas\Mvc\Exception\DomainException;
use Laminas\Mvc\Exception\InvalidArgumentException;
use Laminas\Mvc\Inject_Application_Event_Interface;
use Laminas\Mvc\Mvc_Event;
use Laminas\View\Model\Model_Interface;
use Laminas\View\Model\View_Model;
use function str_replace;
/**
 * Controller Plugin to assist in selecting an appropriate View Model type based on the
 * User Agent's accept header.
 */
class Acceptable_View_Model_Selector extends Abstract_Plugin
{
    /**
     * @var string the Key to inject the name of a viewmodel with in an Accept Header
     */
    public const INJECT_VIEWMODEL_NAME = '_internalViewModel';
    /** @var MvcEvent */
    protected $event;
    /** @var Request */
    protected $request;
    /**
     * Default array to match against.
     *
     * @var Array
     */
    protected $default_match_against;
    /** @var string Default ViewModel */
    protected $default_view_model_name = View_Model::class;
    /**
     * Detects an appropriate viewmodel for request.
     *
     * @param array $matchAgainst (optional) The Array to match against
     * @param bool $returnDefault (optional) If no match is available. Return default instead
     * @param AbstractFieldValuePart|null $resultReference (optional) The object that was matched
     * @throws InvalidArgumentException If the supplied and matched View Model could not be found.
     * @return ModelInterface|null
     */
    public function __invoke(?array $match_against = null, $return_default = true, &$result_reference = null)
    {
        return $this->get_view_model($match_against, $return_default, $result_reference);
    }
    /**
     * Detects an appropriate viewmodel for request.
     *
     * @param array $matchAgainst (optional) The Array to match against
     * @param bool $returnDefault (optional) If no match is available. Return default instead
     * @param AbstractFieldValuePart|null $resultReference (optional) The object that was matched
     * @throws InvalidArgumentException If the supplied and matched View Model could not be found.
     * @return ModelInterface|null
     */
    public function get_view_model(?array $match_against = null, $return_default = true, &$result_reference = null)
    {
        $name = $this->get_view_model_name($match_against, $return_default, $result_reference);
        if (!$name) {
            return;
        }
        if (!class_exists($name)) {
            throw new InvalidArgumentException('The supplied View Model could not be found');
        }
        return new $name();
    }
    /**
     * Detects an appropriate viewmodel name for request.
     *
     * @param array $matchAgainst (optional) The Array to match against
     * @param bool $returnDefault (optional) If no match is available. Return default instead
     * @param AbstractFieldValuePart|null $resultReference (optional) The object that was matched.
     * @return ModelInterface|null Returns null if $returnDefault = false and no match could be made
     */
    public function get_view_model_name(?array $match_against = null, $return_default = true, &$result_reference = null)
    {
        $res = $this->match($match_against);
        if ($res) {
            $result_reference = $res;
            return $this->extract_view_model_name($res);
        }
        if ($return_default) {
            return $this->default_view_model_name;
        }
    }
    /**
     * Detects an appropriate viewmodel name for request.
     *
     * @param array $matchAgainst (optional) The Array to match against
     * @return AbstractFieldValuePart|null The object that was matched
     */
    public function match(?array $match_against = null)
    {
        $request = $this->get_request();
        $headers = $request->get_headers();
        if (!$match_against && !$this->default_match_against || !$headers->has('accept')) {
            return;
        }
        if (!$match_against) {
            $match_against = $this->default_match_against;
        }
        $match_against_string = '';
        foreach ($match_against as $model_name => $model_strings) {
            foreach ((array) $model_strings as $model_string) {
                $match_against_string .= $this->inject_view_model_name($model_string, $model_name);
            }
        }
        /** @var Accept $accept */
        $accept = $headers->get('Accept');
        if (($res = $accept->match($match_against_string)) === false) {
            return;
        }
        return $res;
    }
    /**
     * Set the default View Model (name) to return if no match could be made
     *
     * @param string $defaultViewModelName The default View Model name
     * @return AcceptableViewModelSelector provides fluent interface
     */
    public function set_default_view_model_name($default_view_model_name): static
    {
        $this->default_view_model_name = (string) $default_view_model_name;
        return $this;
    }
    /**
     * Set the default View Model (name) to return if no match could be made
     *
     * @return string
     */
    public function get_default_view_model_name()
    {
        return $this->default_view_model_name;
    }
    /**
     * Set the default Accept Types and View Model combinations to match against if none are specified.
     *
     * @param array $matchAgainst (optional) The Array to match against
     * @return AcceptableViewModelSelector provides fluent interface
     */
    public function set_default_match_against(?array $match_against = null): static
    {
        $this->default_match_against = $match_against;
        return $this;
    }
    /**
     * Get the default Accept Types and View Model combinations to match against if none are specified.
     *
     * @return array|null
     */
    public function get_default_match_against()
    {
        return $this->default_match_against;
    }
    /**
     * Inject the viewmodel name into the accept header string
     *
     * @param string $modelAcceptString
     * @param string $modelName
     */
    protected function inject_view_model_name($model_accept_string, $model_name): string
    {
        $model_name = str_replace('\\', '|', $model_name);
        $model_accept_string = is_array($model_accept_string) ? $model_accept_string[key($model_accept_string)] : $model_accept_string;
        return $model_accept_string . '; ' . self::INJECT_VIEWMODEL_NAME . '="' . $model_name . '", ';
    }
    /**
     * Extract the viewmodel name from a match
     *
     * @return string
     */
    protected function extract_view_model_name(Abstract_Field_Value_Part $res): string|array
    {
        $model_name = $res->get_matched_against()->params[self::INJECT_VIEWMODEL_NAME];
        return str_replace('|', '\\', $model_name);
    }
    /**
     * Get the request
     *
     * @return Request
     * @throws DomainException If unable to find request.
     */
    protected function get_request()
    {
        if ($this->request) {
            return $this->request;
        }
        $event = $this->get_event();
        $request = $event->get_request();
        if (!$request instanceof Request) {
            throw new DomainException('The event used does not contain a valid Request, but must.');
        }
        $this->request = $request;
        return $request;
    }
    /**
     * Get the event
     *
     * @return MvcEvent
     * @throws DomainException If unable to find event.
     */
    protected function get_event()
    {
        if ($this->event) {
            return $this->event;
        }
        $controller = $this->get_controller();
        if (!$controller instanceof Inject_Application_Event_Interface) {
            throw new DomainException('A controller that implements InjectApplicationEventInterface ' . 'is required to use ' . self::class);
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
}