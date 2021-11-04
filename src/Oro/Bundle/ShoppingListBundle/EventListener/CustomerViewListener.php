<?php

namespace Oro\Bundle\ShoppingListBundle\EventListener;

use Oro\Bundle\CustomerBundle\EventListener\AbstractCustomerViewListener;

/**
 * Adds grid with related shopping lists to view pages of Customer and CustomerUser entities.
 */
class CustomerViewListener extends AbstractCustomerViewListener
{
    /**
     * {@inheritdoc}
     */
    protected function getCustomerViewTemplate()
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
    protected function getCustomerUserViewTemplate()
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
