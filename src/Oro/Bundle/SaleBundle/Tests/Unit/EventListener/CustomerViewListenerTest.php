<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\EventListener;

use Oro\Bundle\CustomerBundle\EventListener\AbstractCustomerViewListener;
use Oro\Bundle\CustomerBundle\Tests\Unit\EventListener\AbstractCustomerViewListenerTest;
use Oro\Bundle\SaleBundle\EventListener\CustomerViewListener;

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
        return '@OroSale/Customer/quote_view.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    protected function getCustomerLabel(): string
    {
        return 'oro.sale.quote.datagrid.customer.label';
    }

    /**
     * {@inheritdoc}
     */
    protected function getCustomerUserViewTemplate(): string
    {
        return '@OroSale/CustomerUser/quote_view.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    protected function getCustomerUserLabel(): string
    {
        return 'oro.sale.quote.datagrid.customer_user.label';
    }
}
