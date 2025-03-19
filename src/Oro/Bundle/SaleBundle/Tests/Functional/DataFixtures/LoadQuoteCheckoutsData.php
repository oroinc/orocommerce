<?php

namespace Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\AbstractLoadCheckouts;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\LoadPaymentMethodsConfigsRuleData;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\Traits\EnabledPaymentMethodIdentifierTrait;

class LoadQuoteCheckoutsData extends AbstractLoadCheckouts
{
    use EnabledPaymentMethodIdentifierTrait;

    public const CHECKOUT_1 = 'quote.checkout.1';
    public const CHECKOUT_2 = 'quote.checkout.2';

    #[\Override]
    protected function getData(): array
    {
        $paymentMethodIdentifier = $this->getPaymentMethodIdentifier($this->container);

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
                'checkout' => ['payment_method' => $paymentMethodIdentifier, 'currency' => 'USD'],
                'lineItems' => new ArrayCollection([$lineItem1, $lineItem2])
            ],
            self::CHECKOUT_2 => [
                'customerUser' => LoadCustomerUserData::LEVEL_1_EMAIL,
                'source' => LoadQuoteProductDemandData::QUOTE_DEMAND_2,
                'checkout' => ['payment_method' => $paymentMethodIdentifier, 'currency' => 'USD']
            ]
        ];
    }

    #[\Override]
    protected function getWorkflowName(): string
    {
        return 'b2b_flow_checkout';
    }

    #[\Override]
    protected function createCheckout(): Checkout
    {
        return new Checkout();
    }

    #[\Override]
    protected function getCheckoutSourceName(): string
    {
        return 'quoteDemand';
    }

    #[\Override]
    public function getDependencies(): array
    {
        return array_merge(
            parent::getDependencies(),
            [
                LoadQuoteProductDemandData::class,
                LoadPaymentMethodsConfigsRuleData::class,
            ]
        );
    }
}
