<?php

declare (strict_types=1);
namespace Laminas\Mvc\Controller\Plugin;

use ArrayAccess;
use Laminas\Http\Header\Header_Interface;
use Laminas\Mvc\Exception\RuntimeException;
use Laminas\Mvc\Inject_Application_Event_Interface;
class Params extends Abstract_Plugin
{
    /**
     * Grabs a param from route match by default.
     *
     * @param string $param
     * @return mixed
     */
    public function __invoke($param = null, mixed $default = null)
    {
        if ($param === null) {
            return $this;
        }
        return $this->from_route($param, $default);
    }
    /**
     * Return all files or a single file.
     *
     * @param  string $name File name to retrieve, or null to get all.
     * @param  mixed $default Default value to use when the file is missing.
     * @return array|ArrayAccess|null
     */
    public function from_files($name = null, mixed $default = null)
    {
        if ($name === null) {
            return $this->get_controller()->get_request()->get_files($name, $default)->to_array();
        }
        return $this->get_controller()->get_request()->get_files($name, $default);
    }
    /**
     * Return all header parameters or a single header parameter.
     *
     * @param  string $header Header name to retrieve, or null to get all.
     * @param  mixed $default Default value to use when the requested header is missing.
     * @return null|HeaderInterface
     */
    public function from_header($header = null, mixed $default = null)
    {
        if ($header === null) {
            return $this->get_controller()->get_request()->get_headers($header, $default)->to_array();
        }
        return $this->get_controller()->get_request()->get_headers($header, $default);
    }
    /**
     * Return all post parameters or a single post parameter.
     *
     * @param string $param Parameter name to retrieve, or null to get all.
     * @param mixed $default Default value to use when the parameter is missing.
     * @return mixed
     */
    public function from_post($param = null, mixed $default = null)
    {
        if ($param === null) {
            return $this->get_controller()->get_request()->get_post($param, $default)->to_array();
        }
        return $this->get_controller()->get_request()->get_post($param, $default);
    }
    /**
     * Return all query parameters or a single query parameter.
     *
     * @param string $param Parameter name to retrieve, or null to get all.
     * @param mixed $default Default value to use when the parameter is missing.
     * @return mixed
     */
    public function from_query($param = null, mixed $default = null)
    {
        if ($param === null) {
            return $this->get_controller()->get_request()->get_query($param, $default)->to_array();
        }
        return $this->get_controller()->get_request()->get_query($param, $default);
    }
    /**
     * Return all route parameters or a single route parameter.
     *
     * @param string $param Parameter name to retrieve, or null to get all.
     * @param mixed $default Default value to use when the parameter is missing.
     * @return mixed
     * @throws RuntimeException
     */
    public function from_route($param = null, mixed $default = null)
    {
        $controller = $this->get_controller();
        if (!$controller instanceof Inject_Application_Event_Interface) {
            throw new RuntimeException('Controllers must implement Laminas\Mvc\InjectApplicationEventInterface to use this plugin.');
        }
        if ($param === null) {
            return $controller->get_event()->get_route_match()->get_params();
        }
        return $controller->get_event()->get_route_match()->get_param($param, $default);
    }
}