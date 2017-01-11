<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\PaymentTermBundle\Method\PaymentTerm;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteProductDemandData;

class LoadQuoteCheckoutsData extends AbstractLoadCheckouts
{
    const CHECKOUT_1 = 'checkout.1';
    const CHECKOUT_2 = 'checkout.2';

    /**
     * {@inheritDoc}
     */
    protected function getData()
    {
        return [
            self::CHECKOUT_1 => [
                'customerUser' => LoadCustomerUserData::EMAIL,
                'source' => LoadQuoteProductDemandData::QUOTE_DEMAND_1,
                'checkout' => ['payment_method' => PaymentTerm::TYPE]
            ],
            self::CHECKOUT_2 => [
                'customerUser' => LoadCustomerUserData::LEVEL_1_EMAIL,
                'source' => LoadQuoteProductDemandData::QUOTE_DEMAND_2,
                'checkout' => ['payment_method' => PaymentTerm::TYPE]
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
            [LoadQuoteProductDemandData::class]
        );
    }
}
