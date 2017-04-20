<?php

namespace Oro\Bundle\PaymentBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class SupportsEntityPaymentContextFactoriesPass implements CompilerPassInterface
{
    const COMPOSITE_SERVICE = 'oro_payment.context.factory.composite_supports_entity';
    const TAG = 'oro_payment.supports_entity_payment_context_factory';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::COMPOSITE_SERVICE)) {
            return;
        }

        $taggedServices = $container->findTaggedServiceIds(self::TAG);
        if (empty($taggedServices)) {
            return;
        }

        $factories = [];
        foreach ($taggedServices as $factory => $value) {
            $factories[] = new Reference($factory);
        }

        $container
            ->getDefinition(self::COMPOSITE_SERVICE)
            ->replaceArgument(0, $factories);
    }
}
