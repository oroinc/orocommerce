<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\PaymentBundle\Method\PaymentTerm;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;

class LoadShoppingListsCheckoutsData extends AbstractLoadCheckouts
{
    const CHECKOUT_1 = 'checkout.1';
    const CHECKOUT_2 = 'checkout.2';
    const CHECKOUT_3 = 'checkout.3';

    /**
     * {@inheritDoc}
     */
    protected function getData()
    {
        return [
            self::CHECKOUT_1 => [
                'source' => LoadShoppingLists::SHOPPING_LIST_1,
                'checkout' => ['payment_method' => PaymentTerm::TYPE]
            ],
            self::CHECKOUT_2 => [
                'source' => LoadShoppingLists::SHOPPING_LIST_2,
                'checkout' => ['payment_method' => PaymentTerm::TYPE]
            ],
            self::CHECKOUT_3 => [
                'source' => LoadShoppingLists::SHOPPING_LIST_3,
                'checkout' => ['payment_method' => PaymentTerm::TYPE]
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
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return array_merge(
            parent::getDependencies(),
            ['Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists']
        );
    }
}
