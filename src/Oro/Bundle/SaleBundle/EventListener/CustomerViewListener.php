<?php

namespace Oro\Bundle\SaleBundle\EventListener;

use Oro\Bundle\CustomerBundle\EventListener\AbstractCustomerViewListener;

/**
 * Adds additional block with quotes grid on the Customer and CustomerUser view pages.
 */
class CustomerViewListener extends AbstractCustomerViewListener
{
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
