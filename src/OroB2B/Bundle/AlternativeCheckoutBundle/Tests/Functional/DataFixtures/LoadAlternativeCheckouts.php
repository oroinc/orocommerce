<?php

namespace OroB2B\Bundle\AlternativeCheckoutBundle\Tests\Functional\DataFixtures;

use OroB2B\Bundle\AlternativeCheckoutBundle\Entity\AlternativeCheckout;
use OroB2B\Bundle\CheckoutBundle\Entity\CheckoutSource;
use OroB2B\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\AbstractLoadCheckouts;

class LoadAlternativeCheckouts extends AbstractLoadCheckouts
{
    /**
     * {@inheritDoc}
     */
    protected function getData()
    {
        return [
            [
                'name' => [
                    'source' => 'quote.demand.1',
                    'checkout' => []
                ],
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
