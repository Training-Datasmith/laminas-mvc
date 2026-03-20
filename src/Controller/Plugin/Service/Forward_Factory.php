<?php

declare (strict_types=1);
namespace Laminas\Mvc\Controller\Plugin\Service;

// phpcs:ignore
use Interop\Container\Container_Interface;
use Laminas\Mvc\Controller\Plugin\Forward;
use Laminas\Service_Manager\Exception\Service_Not_Created_Exception;
use Laminas\Service_Manager\Factory\Factory_Interface;
use function sprintf;
class Forward_Factory implements Factory_Interface
{
    /**
     * {@inheritDoc}
     *
     * @throws ServiceNotCreatedException If Controllermanager service is not found in application service locator.
     */
    public function __invoke(Container_Interface $container, $requested_name, ?array $options = null): \Laminas\Mvc\Controller\Plugin\Forward
    {
        if (!$container->has('ControllerManager')) {
            throw new Service_Not_Created_Exception(sprintf('%s requires that the application service manager contains a "%s" service; none found', self::class, 'ControllerManager'));
        }
        $controllers = $container->get('ControllerManager');
        return new Forward($controllers);
    }
}