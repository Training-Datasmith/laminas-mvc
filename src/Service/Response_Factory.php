<?php

declare (strict_types=1);
namespace Laminas\Mvc\Service;

// phpcs:ignore
use Interop\Container\Container_Interface;
use Laminas\Http\Php_Environment\Response as HttpResponse;
use Laminas\Service_Manager\Factory\Factory_Interface;
class Response_Factory implements Factory_Interface
{
    /**
     * Create and return a response instance.
     *
     * @param  string $requestedName
     * @return HttpResponse
     */
    public function __invoke(Container_Interface $container, $requested_name, ?array $options = null)
    {
        return new Http_Response();
    }
}