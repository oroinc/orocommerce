<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\LoadPaymentMethodsConfigsRuleData;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\LoadPaymentTermData;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\Traits\EnabledPaymentMethodIdentifierTrait;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;

class LoadShoppingListsCheckoutsData extends AbstractLoadCheckouts
{
    use EnabledPaymentMethodIdentifierTrait;

    const CHECKOUT_1 = 'checkout.1';
    const CHECKOUT_2 = 'checkout.2';
    const CHECKOUT_3 = 'checkout.3';
    const CHECKOUT_7 = 'checkout.7';

    /**
     * {@inheritDoc}
     */
    protected function getData()
    {
        $paymentTermIdentifier = $this->getPaymentMethodIdentifier($this->container);
        $lineItem1 = (new CheckoutLineItem())
            ->setQuantity(10)
            ->setPrice(Price::create(100, 'USD'));
        $lineItem2 = (new CheckoutLineItem())
            ->setQuantity(20)
            ->setPrice(Price::create(200, 'USD'));

        return [
            self::CHECKOUT_1 => [
                'source' => LoadShoppingLists::SHOPPING_LIST_1,
                'checkout' => ['payment_method' => $paymentTermIdentifier, 'shippingCostAmount' => 10],
                'lineItems' => new ArrayCollection([$lineItem1, $lineItem2]),
                'checkoutSubtotals' => [['currency' => 'USD', 'amount' => 500]],
            ],
            self::CHECKOUT_2 => [
                'source' => LoadShoppingLists::SHOPPING_LIST_2,
                'checkout' => ['payment_method' => $paymentTermIdentifier],
                'checkoutSubtotals' => [['currency' => 'USD', 'amount' => 300]],
            ],
            self::CHECKOUT_3 => [
                'source' => LoadShoppingLists::SHOPPING_LIST_3,
                'checkout' => ['payment_method' => $paymentTermIdentifier],
                'checkoutSubtotals' => [['currency' => 'USD', 'amount' => 100]],
            ],
            self::CHECKOUT_7 => [
                'source' => LoadShoppingLists::SHOPPING_LIST_7,
                'checkout' => ['payment_method' => $paymentTermIdentifier],
                'customerUser' => LoadCustomerUserData::LEVEL_1_EMAIL,
                'checkoutSubtotals' => [['currency' => 'USD', 'amount' => 200]],
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getWorkflowName()
    {
        return 'b2b_flow_checkout';
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
        return 'shoppingList';
    }

    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return array_merge(
            parent::getDependencies(),
            [
                LoadShoppingLists::class,
                LoadPaymentTermData::class,
                LoadPaymentMethodsConfigsRuleData::class,
            ]
        );
    }
}
