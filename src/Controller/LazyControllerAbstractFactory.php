<?php

declare (strict_types=1);
namespace Laminas\Mvc\Controller;

// phpcs:ignore
use function array_map;
use function class_exists;
use function class_implements;
use function in_array;
use Interop\Container\Container_Interface;
use Laminas\Console\Adapter\Adapter_Interface as ConsoleAdapterInterface;
use Laminas\Filter\Filter_Plugin_Manager;
use Laminas\Hydrator\Hydrator_Plugin_Manager;
use Laminas\Input_Filter\Input_Filter_Plugin_Manager;
use Laminas\Log\Filter_Plugin_Manager as LogFilterManager;
use Laminas\Log\Formatter_Plugin_Manager as LogFormatterManager;
use Laminas\Log\Processor_Plugin_Manager as LogProcessorManager;
use Laminas\Log\Writer_Plugin_Manager as LogWriterManager;
use Laminas\Mvc\Exception\DomainException;
use Laminas\Serializer\Adapter_Plugin_Manager as SerializerAdapterManager;
use Laminas\Service_Manager\Exception\Service_Not_Found_Exception;
use Laminas\Service_Manager\Factory\Abstract_Factory_Interface;
use Laminas\Stdlib\Dispatchable_Interface;
use Laminas\Validator\Validator_Plugin_Manager;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use function sprintf;
/**
 * Reflection-based factory for controllers.
 *
 * To ease development, this factory may be used for controllers with
 * type-hinted arguments that resolve to services in the application
 * container; this allows omitting the step of writing a factory for
 * each controller.
 *
 * You may use it as either an abstract factory:
 *
 * <code>
 * 'controllers' => [
 *     'abstract_factories' => [
 *         LazyControllerAbstractFactory::class,
 *     ],
 * ],
 * </code>
 *
 * Or as a factory, mapping a controller class name to it:
 *
 * <code>
 * 'controllers' => [
 *     'factories' => [
 *         MyControllerWithDependencies::class => LazyControllerAbstractFactory::class,
 *     ],
 * ],
 * </code>
 *
 * The latter approach is more explicit, and also more performant.
 *
 * The factory has the following constraints/features:
 *
 * - A parameter named `$config` typehinted as an array will receive the
 *   application "config" service (i.e., the merged configuration).
 * - Parameters type-hinted against array, but not named `$config` will
 *   be injected with an empty array.
 * - Scalar parameters will be resolved as null values.
 * - If a service cannot be found for a given typehint, the factory will
 *   raise an exception detailing this.
 * - Some services provided by Laminas components do not have
 *   entries based on their class name (for historical reasons); the
 *   factory contains a map of these class/interface names to the
 *   corresponding service name to allow them to resolve.
 *
 * `$options` passed to the factory are ignored in all cases, as we cannot
 * make assumptions about which argument(s) they might replace.
 */
class Lazy_Controller_Abstract_Factory implements Abstract_Factory_Interface
{
    /**
     * Maps known classes/interfaces to the service that provides them; only
     * required for those services with no entry based on the class/interface
     * name.
     *
     * Extend the class if you wish to add to the list.
     *
     * @var string[]
     */
    protected $aliases = [Console_Adapter_Interface::class => 'ConsoleAdapter', Filter_Plugin_Manager::class => 'FilterManager', Hydrator_Plugin_Manager::class => 'HydratorManager', Input_Filter_Plugin_Manager::class => 'InputFilterManager', Log_Filter_Manager::class => 'LogFilterManager', Log_Formatter_Manager::class => 'LogFormatterManager', Log_Processor_Manager::class => 'LogProcessorManager', Log_Writer_Manager::class => 'LogWriterManager', Serializer_Adapter_Manager::class => 'SerializerAdapterManager', Validator_Plugin_Manager::class => 'ValidatorManager'];
    /**
     * {@inheritDoc}
     *
     * @return DispatchableInterface
     */
    public function __invoke(Container_Interface $container, $requested_name, ?array $options = null)
    {
        $reflection_class = new ReflectionClass($requested_name);
        if (null === $constructor = $reflection_class->get_constructor()) {
            return new $requested_name();
        }
        $reflection_parameters = $constructor->get_parameters();
        if (empty($reflection_parameters)) {
            return new $requested_name();
        }
        $parameters = array_map($this->resolve_parameter($container, $requested_name), $reflection_parameters);
        return new $requested_name(...$parameters);
    }
    /**
     * {@inheritDoc}
     */
    public function can_create(Container_Interface $container, $requested_name)
    {
        if (!class_exists($requested_name)) {
            return false;
        }
        return in_array(Dispatchable_Interface::class, class_implements($requested_name), true);
    }
    /**
     * Resolve a parameter to a value.
     *
     * Returns a callback for resolving a parameter to a value.
     *
     * @param string $requestedName
     * @return callable
     */
    private function resolve_parameter(Container_Interface $container, $requested_name)
    {
        /**
         * @param ReflectionParameter $parameter
         * @return mixed
         * @throws ServiceNotFoundException If type-hinted parameter cannot be
         *   resolved to a service in the container.
         */
        return function (ReflectionParameter $parameter) use ($container, $requested_name) {
            $parameter_type = $parameter->get_type();
            if ($parameter_type === null) {
                return null;
            }
            if (!$parameter_type instanceof ReflectionNamedType) {
                throw new DomainException(sprintf('Unable to create controller "%s"; unable to resolve parameter "%s" with union type hint', $requested_name, $parameter->get_name()));
            }
            if ($parameter_type->get_name() === 'array') {
                if ($parameter->get_name() === 'config' && $container->has('config')) {
                    return $container->get('config');
                }
                return [];
            }
            if ($parameter_type->is_builtin()) {
                return null;
            }
            $type = $parameter_type->get_name();
            $type = $this->aliases[$type] ?? $type;
            if (!$container->has($type)) {
                throw new Service_Not_Found_Exception(sprintf('Unable to create controller "%s"; unable to resolve parameter "%s" using type hint "%s"', $requested_name, $parameter->get_name(), $type));
            }
            return $container->get($type);
        };
    }
}