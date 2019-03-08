<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\EventListener;

use Oro\Bundle\CustomerBundle\Tests\Unit\EventListener\AbstractCustomerViewListenerTest;
use Oro\Bundle\SaleBundle\EventListener\CustomerViewListener;

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
        return 'OroSaleBundle:Customer:quote_view.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    protected function getCustomerLabel()
    {
        return 'oro.sale.quote.datagrid.customer.label';
    }

    /**
     * {@inheritdoc}
     */
    protected function getCustomerUserViewTemplate()
    {
        return 'OroSaleBundle:CustomerUser:quote_view.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    protected function getCustomerUserLabel()
    {
        return 'oro.sale.quote.datagrid.customer_user.label';
    }
}
