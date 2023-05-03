<?php

namespace Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\AbstractLoadCheckouts;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\LoadPaymentMethodsConfigsRuleData;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\LoadPaymentTermData;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\Traits\EnabledPaymentMethodIdentifierTrait;

class LoadQuoteCompletedCheckoutsData extends AbstractLoadCheckouts
{
    use EnabledPaymentMethodIdentifierTrait;

    public const CHECKOUT_1 = 'checkout.1';

    /**
     * {@inheritDoc}
     */
    protected function getData(): array
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        return [
            self::CHECKOUT_1 => [
                'customerUser' => LoadCustomerUserData::LEVEL_1_EMAIL,
                'source' => LoadQuoteProductDemandData::QUOTE_DEMAND_2,
                'checkout' => ['payment_method' => $this->getPaymentMethodIdentifier($this->container)],
                'completed' => true,
                'completedData' => [
                    'itemsCount' => \count($order->getLineItems()),
                    'orders' => [
                        [
                            'entityAlias' => 'order',
                            'entityId' => ['id' => $order->getId()]
                        ]
                    ],
                    'startedFrom' => LoadQuoteProductDemandData::QUOTE_DEMAND_2,
                    'currency' => $order->getCurrency(),
                    'subtotal' => $order->getSubtotal(),
                    'total' => $order->getTotal()
                ]
            ]
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getWorkflowName(): string
    {
        return 'b2b_flow_checkout';
    }

    /**
     * {@inheritDoc}
     */
    protected function createCheckout(): Checkout
    {
        return new Checkout();
    }

    /**
     * {@inheritDoc}
     */
    protected function getCheckoutSourceName(): string
    {
        return 'quoteDemand';
    }

    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return array_merge(
            parent::getDependencies(),
            [
                LoadQuoteProductDemandData::class,
                LoadOrders::class,
                LoadPaymentTermData::class,
                LoadPaymentMethodsConfigsRuleData::class,
            ]
        );
    }
}
