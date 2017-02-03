<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\PaymentTermBundle\Method\PaymentTerm;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteProductDemandData;

class LoadQuoteCompletedCheckoutsData extends AbstractLoadCheckouts
{
    const CHECKOUT_1 = 'checkout.1';

    /**
     * {@inheritDoc}
     */
    protected function getData()
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        return [
            self::CHECKOUT_1 => [
                'customerUser' => LoadCustomerUserData::LEVEL_1_EMAIL,
                'source' => LoadQuoteProductDemandData::QUOTE_DEMAND_2,
                'checkout' => ['payment_method' => PaymentTerm::TYPE],
                'completed' => true,
                'completedData' => [
                    'itemsCount' => count($order->getLineItems()),
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
     * @return string
     */
    protected function getWorkflowName()
    {
        return 'b2b_flow_checkout';
    }

    /**
     * @return Checkout
     */
    protected function createCheckout()
    {
        return new Checkout();
    }

    /**
     * {@inheritDoc}
     */
    protected function getCheckoutSourceName()
    {
        return 'quoteDemand';
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return array_merge(
            parent::getDependencies(),
            [LoadQuoteProductDemandData::class, LoadOrders::class]
        );
    }
}
