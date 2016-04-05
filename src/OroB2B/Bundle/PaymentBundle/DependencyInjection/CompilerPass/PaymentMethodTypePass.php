<?php

namespace OroB2B\Bundle\PaymentBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class PaymentMethodTypePass implements CompilerPassInterface
{
    const REGISTRY_SERVICE = 'orob2b_payment.form.payment_method_type_registry';
    const TAG = 'orob2b_payment.payment_method_type';

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

        $paymentTypes = [];
        foreach ($taggedServices as $id => $attributes) {
            $priority = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;
            $paymentTypes[$priority][] = $id;
        }

        krsort($paymentTypes);
        $paymentTypes = call_user_func_array('array_merge', $paymentTypes);

        foreach ($paymentTypes as $paymentType) {
            $registryDefinition->addMethodCall('addPaymentMethodType', [new Reference($paymentType)]);
        }
    }
}
