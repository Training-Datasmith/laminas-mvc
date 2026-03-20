<?php

declare (strict_types=1);
namespace Laminas\Mvc\Controller\Plugin;

use Laminas\Stdlib\Dispatchable_Interface as Dispatchable;
abstract class Abstract_Plugin implements Plugin_Interface
{
    /** @var null|Dispatchable */
    protected $controller;
    /**
     * Set the current controller instance
     */
    public function set_controller(Dispatchable $controller): void
    {
        $this->controller = $controller;
    }
    /**
     * Get the current controller instance
     *
     * @return null|Dispatchable
     */
    public function get_controller()
    {
        return $this->controller;
    }
}