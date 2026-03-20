<?php

declare (strict_types=1);
namespace Laminas\Mvc\Exception;

/**
 * @deprecated Since 3.2.0
 */
class Reached_Final_Handler_Exception extends RuntimeException
{
    public static function create(): self
    {
        return new self('Reached the final handler for middleware pipe - check the pipe configuration');
    }
}