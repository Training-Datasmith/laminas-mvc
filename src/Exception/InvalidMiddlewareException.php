<?php

namespace Laminas\Mvc\Exception;

use function sprintf;

/**
 * @deprecated Since 3.2.0
 */
class InvalidMiddlewareException extends RuntimeException
{
    private ?string $middlewareName = null;

    /**
     * @param string $middlewareName
     */
    public static function fromMiddlewareName($middlewareName): self
    {
        $middlewareName           = (string) $middlewareName;
        $instance                 = new self(sprintf('Cannot dispatch middleware %s', $middlewareName));
        $instance->middlewareName = $middlewareName;
        return $instance;
    }

    public static function fromNull(): self
    {
        return new self('Middleware name cannot be null');
    }

    public function toMiddlewareName(): string
    {
        return $this->middlewareName ?? '';
    }
}
