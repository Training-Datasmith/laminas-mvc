<?php

declare (strict_types=1);
namespace Laminas\Mvc\Exception;

use function sprintf;
/**
 * @deprecated Since 3.2.0
 */
class Invalid_Middleware_Exception extends RuntimeException
{
    private ?string $middleware_name = null;
    /**
     * @param string $middlewareName
     */
    public static function from_middleware_name($middleware_name): self
    {
        $middleware_name = (string) $middleware_name;
        $instance = new self(sprintf('Cannot dispatch middleware %s', $middleware_name));
        $instance->middleware_name = $middleware_name;
        return $instance;
    }
    public static function from_null(): self
    {
        return new self('Middleware name cannot be null');
    }
    public function to_middleware_name(): string
    {
        return $this->middleware_name ?? '';
    }
}