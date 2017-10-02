<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteProductDemandData;

class LoadQuoteCheckoutsData extends AbstractLoadCheckouts
{
    const CHECKOUT_1 = 'checkout.1';
    const CHECKOUT_2 = 'checkout.2';

    const PAYMENT_METHOD = 'payment_term';

    /**
     * {@inheritDoc}
     */
    protected function getData()
    {
        $lineItem1 = (new CheckoutLineItem())
            ->setQuantity(10)
            ->setPrice(Price::create(100, 'USD'));
        $lineItem2 = (new CheckoutLineItem())
            ->setQuantity(20)
            ->setPrice(Price::create(200, 'USD'));

        return [
            self::CHECKOUT_1 => [
                'customerUser' => LoadCustomerUserData::EMAIL,
                'source' => LoadQuoteProductDemandData::QUOTE_DEMAND_1,
                'checkout' => ['payment_method' => self::PAYMENT_METHOD, 'currency' => 'USD'],
                'lineItems' => new ArrayCollection([$lineItem1, $lineItem2])
            ],
            self::CHECKOUT_2 => [
                'customerUser' => LoadCustomerUserData::LEVEL_1_EMAIL,
                'source' => LoadQuoteProductDemandData::QUOTE_DEMAND_2,
                'checkout' => ['payment_method' => self::PAYMENT_METHOD, 'currency' => 'USD']
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
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return array_merge(
            parent::getDependencies(),
            [LoadQuoteProductDemandData::class]
        );
    }
}
