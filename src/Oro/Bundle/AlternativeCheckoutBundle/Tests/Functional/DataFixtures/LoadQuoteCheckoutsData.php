<?php

namespace Oro\Bundle\AlternativeCheckoutBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\AbstractLoadCheckouts;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\LoadPaymentMethodsConfigsRuleData;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\LoadPaymentTermData;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\Traits\EnabledPaymentMethodIdentifierTrait;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteProductDemandData;

class LoadQuoteCheckoutsData extends AbstractLoadCheckouts
{
    use EnabledPaymentMethodIdentifierTrait;

    const CHECKOUT_1 = 'alternative.checkout.1';
    const CHECKOUT_2 = 'alternative.checkout.2';

    /**
     * {@inheritDoc}
     */
    protected function getData()
    {
        $paymentMethodIdentifier = $this->getPaymentMethodIdentifier($this->container);

        return [
            self::CHECKOUT_1 => [
                'customerUser' => LoadCustomerUserData::EMAIL,
                'source' => LoadQuoteProductDemandData::QUOTE_DEMAND_1,
                'checkout' => ['payment_method' => $paymentMethodIdentifier]
            ],
            self::CHECKOUT_2 => [
                'source' => LoadQuoteProductDemandData::QUOTE_DEMAND_2,
                'checkout' => ['payment_method' => $paymentMethodIdentifier]
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
            [
                LoadQuoteProductDemandData::class,
                LoadPaymentTermData::class,
                LoadPaymentMethodsConfigsRuleData::class,
            ]
        );
    }
}
