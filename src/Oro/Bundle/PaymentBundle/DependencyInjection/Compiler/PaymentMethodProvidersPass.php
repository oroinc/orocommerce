<?php

namespace Oro\Bundle\PaymentBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @deprecated since 1.1
 *
 * @see \Oro\Bundle\PaymentBundle\DependencyInjection\Compiler\CompositePaymentMethodProviderCompilerPass
 */
class PaymentMethodProvidersPass implements CompilerPassInterface
{
    const REGISTRY_SERVICE = 'oro_payment.payment_method_provider.registry';
    const TAG = 'oro_payment.payment_method_provider';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::REGISTRY_SERVICE)) {
            return;
        }

        $taggedServices = $container->findTaggedServiceIds(self::TAG);
        if (empty($taggedServices)) {
            return;
        }

        $registryDefinition = $container->getDefinition(self::REGISTRY_SERVICE);

        foreach ($taggedServices as $method => $value) {
            $registryDefinition->addMethodCall('addProvider', [new Reference($method)]);
        }
    }
}
