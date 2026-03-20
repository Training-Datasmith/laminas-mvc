<?php

declare (strict_types=1);
namespace Laminas\Mvc\Service;

// phpcs:ignore
use Interop\Container\Container_Interface;
use Laminas\Service_Manager\Factory\Factory_Interface;
use Laminas\View\Strategy\Feed_Strategy;
class View_Feed_Strategy_Factory implements Factory_Interface
{
    /**
     * Create and return the Feed view strategy
     *
     * Retrieves the ViewFeedRenderer service from the service locator, and
     * injects it into the constructor for the feed strategy.
     *
     * It then attaches the strategy to the View service, at a priority of 100.
     *
     * @param  string $requestedName
     * @return FeedStrategy
     */
    public function __invoke(Container_Interface $container, $requested_name, ?array $options = null)
    {
        return new Feed_Strategy($container->get('ViewFeedRenderer'));
    }
}