<?php

namespace Oro\Bundle\PaymentBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class CompositePaymentMethodProviderCompilerPass implements CompilerPassInterface
{
    const COMPOSITE_SERVICE = 'oro_payment.payment_method.composite_provider';
    const TAG = 'oro_payment.payment_method_provider';

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

        $compiledServiceDefinition = $container->getDefinition(self::COMPOSITE_SERVICE);

        foreach ($taggedServices as $method => $value) {
            $compiledServiceDefinition->addMethodCall('addProvider', [new Reference($method)]);
        }
    }
}
