<?php

namespace Oro\Bundle\InfinitePayBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class ActionsCompilerPass implements CompilerPassInterface
{
    const REGISTRY_PAYMENT_ACTIONS = 'oro_infinite_pay.registry.payment_actions';

    const TAG_PAYMENT_ACTION = 'payment_action';

    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(static::REGISTRY_PAYMENT_ACTIONS)) {
            return;
        }
        $services = $container->findTaggedServiceIds(static::TAG_PAYMENT_ACTION);
        if (empty($services)) {
            return;
        }
        $registryDefinition = $container->getDefinition(static::REGISTRY_PAYMENT_ACTIONS);
        $this->populateRegistry($services, $registryDefinition);
    }

    /**
     * @param array      $services
     * @param Definition $registry
     */
    private function populateRegistry($services, $registry)
    {
        foreach ($services as $serviceKey => $tags) {
            foreach ($tags as $attributes) {
                $actionReference = new Reference($serviceKey);
                $registry->addMethodCall('addAction', [$attributes['type'], $actionReference]);
            }
        }
    }
}
