<?php

namespace Oro\Bundle\SaleBundle\EventListener;

use Oro\Bundle\CustomerBundle\EventListener\AbstractCustomerViewListener;

/**
 * Adds additional block with quotes grid on the Customer and CustomerUser view pages.
 */
class CustomerViewListener extends AbstractCustomerViewListener
{
    #[\Override]
    protected function getCustomerViewTemplate()
    {
        return '@OroSale/Customer/quote_view.html.twig';
    }

    #[\Override]
    protected function getCustomerLabel(): string
    {
        return 'oro.sale.quote.datagrid.customer.label';
    }

    #[\Override]
    protected function getCustomerUserViewTemplate()
    {
        return '@OroSale/CustomerUser/quote_view.html.twig';
    }

    #[\Override]
    protected function getCustomerUserLabel(): string
    {
        return 'oro.sale.quote.datagrid.customer_user.label';
    }
}
