<?php

declare (strict_types=1);
namespace Laminas\Mvc\Service;

use ArrayAccess;
// phpcs:ignore
use Interop\Container\Container_Interface;
use function is_array;
trait Http_View_Manager_Config_Trait
{
    /**
     * Retrieve view_manager configuration, if present.
     *
     * @return array
     */
    private function get_config(Container_Interface $container): \ArrayAccess|array
    {
        $config = $container->has('config') ? $container->get('config') : [];
        if (isset($config['view_manager']) && (is_array($config['view_manager']) || $config['view_manager'] instanceof ArrayAccess)) {
            return $config['view_manager'];
        }
        return [];
    }
}