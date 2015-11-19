<?php

namespace Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class FunctionPass implements CompilerPassInterface
{
    const ACTION_TAG                    = 'oro_action.function';
    const ACTION_FACTORY_SERVICE        = 'oro_action.function_factory';
    const EVENT_DISPATCHER_SERVICE      = 'event_dispatcher';
    const EVENT_DISPATCHER_AWARE_ACTION = 'Oro\Bundle\WorkflowBundle\Model\Action\EventDispatcherAwareActionInterface';

    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $functions = $container->findTaggedServiceIds(self::ACTION_TAG);
        $types = [];

        foreach ($functions as $id => $attributes) {
            $definition = $container->getDefinition($id);
            $definition->setScope(ContainerInterface::SCOPE_PROTOTYPE)->setPublic(false);

            $className = $definition->getClass();
            $refClass = new \ReflectionClass($className);
            if ($refClass->implementsInterface(self::EVENT_DISPATCHER_AWARE_ACTION)) {
                $definition->addMethodCall('setDispatcher', [new Reference(self::EVENT_DISPATCHER_SERVICE)]);
            }

            foreach ($attributes as $eachTag) {
                if (!empty($eachTag['alias'])) {
                    $aliases = explode('|', $eachTag['alias']);
                } else {
                    $aliases = [$id];
                }
                foreach ($aliases as $alias) {
                    $types[$alias] = $id;
                }
            }
        }

        $definition = $container->getDefinition(self::ACTION_FACTORY_SERVICE);
        $definition->replaceArgument(1, $types);
    }
}
