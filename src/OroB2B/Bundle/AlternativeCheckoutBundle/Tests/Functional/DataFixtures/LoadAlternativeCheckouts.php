<?php

namespace OroB2B\Bundle\AlternativeCheckoutBundle\Tests\Functional\DataFixtures;

use OroB2B\Bundle\AlternativeCheckoutBundle\Entity\AlternativeCheckout;
use OroB2B\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\AbstractLoadCheckouts;
use OroB2B\Bundle\PaymentBundle\Method\PayflowGateway;
use OroB2B\Bundle\PaymentBundle\Method\PaymentTerm;
use OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteProductDemandData;

class LoadAlternativeCheckouts extends AbstractLoadCheckouts
{
    const CHECKOUT_1 = 'alternative.checkout.1';
    const CHECKOUT_2 = 'alternative.checkout.2';

    /**
     * {@inheritDoc}
     */
    protected function getData()
    {
        return [
            self::CHECKOUT_1 => [
                'source' => LoadQuoteProductDemandData::QUOTE_DEMAND_1,
                'checkout' => ['payment_method' => PayflowGateway::TYPE]
            ],
            self::CHECKOUT_2 => [
                'source' => LoadQuoteProductDemandData::QUOTE_DEMAND_2,
                'checkout' => ['payment_method' => PaymentTerm::TYPE]
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getWorkflowName()
    {
        return 'b2b_flow_alternative_checkout';
    }

    /**
     * {@inheritDoc}
     */
    protected function createCheckout()
    {
        return new AlternativeCheckout();
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
            ['OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteProductDemandData']
        );
    }
}
