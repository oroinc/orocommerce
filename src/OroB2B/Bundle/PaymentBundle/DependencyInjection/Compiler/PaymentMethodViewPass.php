<?php

namespace Oro\Bundle\PaymentBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class PaymentMethodViewPass implements CompilerPassInterface
{
    const REGISTRY_SERVICE = 'orob2b_payment.payment_method.view.registry';
    const TAG = 'orob2b_payment.payment_method_view';

    /**
     * {@inheritdoc}
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

        foreach ($taggedServices as $id => $attributes) {
            $registryDefinition->addMethodCall('addPaymentMethodView', [new Reference($id)]);
        }
    }
}
