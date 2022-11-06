<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\EventListener;

use Oro\Bundle\CustomerBundle\EventListener\AbstractCustomerViewListener;
use Oro\Bundle\CustomerBundle\Tests\Unit\EventListener\AbstractCustomerViewListenerTest;
use Oro\Bundle\ShoppingListBundle\EventListener\CustomerViewListener;

class CustomerViewListenerTest extends AbstractCustomerViewListenerTest
{
    /**
     * {@inheritdoc}
     */
    protected function createListenerToTest(): AbstractCustomerViewListener
    {
        return new CustomerViewListener(
            $this->translator,
            $this->doctrineHelper,
            $this->requestStack
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getCustomerViewTemplate(): string
    {
        return '@OroShoppingList/Customer/shopping_lists_view.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    protected function getCustomerLabel(): string
    {
        return 'oro.shoppinglist.entity_plural_label';
    }

    /**
     * {@inheritdoc}
     */
    protected function getCustomerUserViewTemplate(): string
    {
        return '@OroShoppingList/CustomerUser/shopping_lists_view.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    protected function getCustomerUserLabel(): string
    {
        return 'oro.shoppinglist.entity_plural_label';
    }
}
