<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\LoadPaymentMethodsConfigsRuleData;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\LoadPaymentTermData;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\Traits\EnabledPaymentMethodIdentifierTrait;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;

class LoadCompletedAndNonCompletedSimpleCheckoutsData extends AbstractLoadCheckouts
{
    use EnabledPaymentMethodIdentifierTrait;

    public const CHECKOUT_COMPLETED = 'checkout_completed';
    public const CHECKOUT_NOT_COMPLETED = 'checkout_not_completed';

    /**
     * {@inheritDoc}
     */
    protected function getData(): array
    {
        $paymentTermIdentifier = $this->getPaymentMethodIdentifier($this->container);

        return [
            self::CHECKOUT_COMPLETED => [
                'source' => LoadShoppingLists::SHOPPING_LIST_1,
                'checkout' => ['payment_method' => $paymentTermIdentifier],
                'completed' => true,
            ],
            self::CHECKOUT_NOT_COMPLETED => [
                'source' => LoadShoppingLists::SHOPPING_LIST_2,
                'checkout' => ['payment_method' => $paymentTermIdentifier],
                'completed' => false,
            ],
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
        return 'shoppingList';
    }

    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
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
