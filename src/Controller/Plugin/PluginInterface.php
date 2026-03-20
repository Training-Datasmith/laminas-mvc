<?php

declare (strict_types=1);
namespace Laminas\Mvc\Controller\Plugin;

use Laminas\Stdlib\Dispatchable_Interface as Dispatchable;
interface Plugin_Interface
{
    /**
     * Set the current controller instance
     *
     * @return void
     */
    public function set_controller(Dispatchable $controller);
    /**
     * Get the current controller instance
     *
     * @return null|Dispatchable
     */
    public function get_controller();
}