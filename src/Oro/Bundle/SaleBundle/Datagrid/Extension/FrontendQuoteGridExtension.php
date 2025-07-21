<?php

namespace Oro\Bundle\SaleBundle\Datagrid\Extension;

use Oro\Bundle\CustomerBundle\Datagrid\Extension\AbstractCustomerVisitorExtension;

/**
 * Data grid extension for the frontend quotes grid (`frontend-quotes-grid`).
 * Applies filtering to show only quotes associated with the current anonymous visitor
 * during frontend requests.
 */
class FrontendQuoteGridExtension extends AbstractCustomerVisitorExtension
{
    public function getGridName(): string
    {
        return 'frontend-quotes-grid';
    }
}
