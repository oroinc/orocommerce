<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\EventListener;

use Oro\Bundle\CustomerBundle\Tests\Unit\EventListener\AbstractCustomerViewListenerTest;
use Oro\Bundle\ShoppingListBundle\EventListener\CustomerViewListener;

class CustomerViewListenerTest extends AbstractCustomerViewListenerTest
{
    /**
     * {@inheritdoc}
     */
    protected function createListenerToTest()
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
