<?php

declare (strict_types=1);
namespace Laminas\Mvc\Service;

// phpcs:ignore
use Interop\Container\Container_Interface;
use Laminas\Service_Manager\Factory\Factory_Interface;
use Laminas\View\Strategy\Json_Strategy;
class View_Json_Strategy_Factory implements Factory_Interface
{
    /**
     * Create and return the JSON view strategy
     *
     * Retrieves the ViewJsonRenderer service from the service locator, and
     * injects it into the constructor for the JSON strategy.
     *
     * It then attaches the strategy to the View service, at a priority of 100.
     *
     * @param  string $requestedName
     * @return JsonStrategy
     */
    public function __invoke(Container_Interface $container, $requested_name, ?array $options = null)
    {
        $json_renderer = $container->get('ViewJsonRenderer');
        return new Json_Strategy($json_renderer);
    }
}