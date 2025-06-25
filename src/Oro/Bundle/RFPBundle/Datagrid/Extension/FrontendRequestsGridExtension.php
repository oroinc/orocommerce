<?php

namespace Oro\Bundle\RFPBundle\Datagrid\Extension;

use Oro\Bundle\CustomerBundle\Datagrid\Extension\AbstractCustomerVisitorExtension;

/**
 * Data grid extension for the frontend requests grid (`frontend-requests-grid`).
 * Applies filtering to show only requests associated with the current anonymous visitor
 * during frontend requests.
 */
class FrontendRequestsGridExtension extends AbstractCustomerVisitorExtension
{
    public function getGridName(): string
    {
        return 'frontend-requests-grid';
    }
}
