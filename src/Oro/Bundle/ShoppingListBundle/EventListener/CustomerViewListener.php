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
        return 'OroShoppingListBundle:Customer:shopping_lists_view.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    protected function getCustomerLabel()
    {
        return 'oro.shoppinglist.entity_plural_label';
    }

    /**
     * {@inheritdoc}
     */
    protected function getCustomerUserViewTemplate()
    {
        return 'OroShoppingListBundle:CustomerUser:shopping_lists_view.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    protected function getCustomerUserLabel()
    {
        return 'oro.shoppinglist.entity_plural_label';
    }
}
