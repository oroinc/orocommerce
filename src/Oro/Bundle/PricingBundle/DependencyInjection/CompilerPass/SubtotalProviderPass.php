<?php

namespace Oro\Bundle\PricingBundle\DependencyInjection\CompilerPass;

use Oro\Component\DependencyInjection\Compiler\PriorityTaggedLocatorTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Registers all subtotal providers.
 */
class SubtotalProviderPass implements CompilerPassInterface
{
    use PriorityTaggedLocatorTrait;

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $services = $this->findAndInverseSortTaggedServices('oro_pricing.subtotal_provider', 'alias', $container);

        $container->getDefinition('oro_pricing.subtotal_processor.subtotal_provider_registry')
            ->setArgument(0, array_keys($services))
            ->setArgument(1, ServiceLocatorTagPass::register($container, $services));
    }
}
