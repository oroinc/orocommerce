<?php

namespace OroB2B\Bundle\MoneyOrderBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class PaymentConfigurationPass implements CompilerPassInterface
{
    const PAYMENT_METHOD_REGISTRY_SERVICE = 'orob2b_payment.payment_method.registry';
    const MONEY_ORDER_PAYMENT_METHOD_SERVICE = 'orob2b_money_order.payment_method.money_order';
    const PAYMENT_METHOD_VIEW_REGISTRY_SERVICE = 'orob2b_payment.payment_method.view.registry';
    const MONEY_ORDER_PAYMENT_METHOD_VIEW_SERVICE = 'orob2b_money_order.payment_method.view.money_order';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition(self::PAYMENT_METHOD_REGISTRY_SERVICE)) {
            $definition = $container->getDefinition(self::PAYMENT_METHOD_REGISTRY_SERVICE);

            $definition->addMethodCall(
                'addPaymentMethod',
                [
                    new Reference(self::MONEY_ORDER_PAYMENT_METHOD_SERVICE)
                ]
            );
        }
        if ($container->hasDefinition(self::PAYMENT_METHOD_VIEW_REGISTRY_SERVICE)) {
            $definition = $container->getDefinition(self::PAYMENT_METHOD_VIEW_REGISTRY_SERVICE);

            $definition->addMethodCall(
                'addPaymentMethodView',
                [
                    new Reference(self::MONEY_ORDER_PAYMENT_METHOD_VIEW_SERVICE)
                ]
            );
        }
    }
}
