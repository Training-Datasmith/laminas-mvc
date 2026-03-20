<?php

declare (strict_types=1);
namespace Laminas\Mvc\Service;

// phpcs:ignore
use function array_key_exists;
use Interop\Container\Container_Interface;
use function is_array;
use Laminas\Mvc\Http_Method_Listener;
use Laminas\Service_Manager\Factory\Factory_Interface;
class Http_Method_Listener_Factory implements Factory_Interface
{
    /**
     * {@inheritdoc}
     */
    public function __invoke(Container_Interface $container, $requested_name, ?array $options = null): \Laminas\Mvc\Http_Method_Listener
    {
        $config = $container->get('config');
        if (!isset($config['http_methods_listener'])) {
            return new Http_Method_Listener();
        }
        $listener_config = $config['http_methods_listener'];
        $enabled = array_key_exists('enabled', $listener_config) ? $listener_config['enabled'] : true;
        $allowed_methods = isset($listener_config['allowed_methods']) && is_array($listener_config['allowed_methods']) ? $listener_config['allowed_methods'] : null;
        return new Http_Method_Listener($enabled, $allowed_methods);
    }
}