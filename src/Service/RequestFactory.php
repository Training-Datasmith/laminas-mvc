<?php

declare (strict_types=1);
namespace Laminas\Mvc\Service;

// phpcs:ignore
use Interop\Container\Container_Interface;
use Laminas\Http\Php_Environment\Request as HttpRequest;
use Laminas\Service_Manager\Factory\Factory_Interface;
class Request_Factory implements Factory_Interface
{
    /**
     * Create and return a request instance.
     *
     * @param  string $requestedName
     * @return HttpRequest
     */
    public function __invoke(Container_Interface $container, $requested_name, ?array $options = null)
    {
        return new Http_Request();
    }
}