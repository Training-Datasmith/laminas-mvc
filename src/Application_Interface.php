<?php

declare (strict_types=1);
namespace Laminas\Mvc;

use Laminas\Event_Manager\Events_Capable_Interface;
use Laminas\Service_Manager\Service_Locator_Interface;
use Laminas\Stdlib\Request_Interface;
use Laminas\Stdlib\Response_Interface;
/**
 * Contract for a Laminas MVC application.
 *
 * An application orchestrates the MVC request/response lifecycle:
 * bootstrap → route → dispatch → render → finish.
 * Implementations receive the service locator, request, and response at
 * construction time and expose them through this interface.
 *
 * @since 3.0.0
 */
interface Application_Interface extends Events_Capable_Interface
{
    /**
     * Return the service locator (IoC container) for this application.
     *
     * @return Service_Locator_Interface The configured service manager
     * @since 3.0.0
     */
    public function get_service_manager(): Service_Locator_Interface;
    /**
     * Return the current HTTP (or CLI) request object.
     *
     * @return Request_Interface The request that triggered this dispatch cycle
     * @since 3.0.0
     */
    public function get_request(): Request_Interface;
    /**
     * Return the current HTTP response object.
     *
     * The response may be mutated by listeners and controllers throughout the
     * route/dispatch/render lifecycle.
     *
     * @return Response_Interface The mutable response for this dispatch cycle
     * @since 3.0.0
     */
    public function get_response(): Response_Interface;
    /**
     * Execute the full MVC request lifecycle.
     *
     * Triggers the route, dispatch, render, and finish events in sequence.
     * After calling run(), use get_response() to obtain the final response.
     *
     * @return static The application instance (for chaining)
     * @since 3.0.0
     */
    public function run(): static;
}