<?php

declare (strict_types=1);
namespace Laminas\Mvc;

use Laminas\Event_Manager\Events_Capable_Interface;
use Laminas\Service_Manager\Service_Locator_Interface;
use Laminas\Stdlib\Request_Interface;
use Laminas\Stdlib\Response_Interface;
interface Application_Interface extends Events_Capable_Interface
{
    /**
     * Get the locator object
     *
     * @return ServiceLocatorInterface
     */
    public function get_service_manager();
    /**
     * Get the request object
     *
     * @return RequestInterface
     */
    public function get_request();
    /**
     * Get the response object
     *
     * @return ResponseInterface
     */
    public function get_response();
    /**
     * Run the application
     *
     * @return self
     */
    public function run();
}