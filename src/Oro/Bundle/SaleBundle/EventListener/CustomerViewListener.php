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
    protected function getCustomerUserViewTemplate()
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
