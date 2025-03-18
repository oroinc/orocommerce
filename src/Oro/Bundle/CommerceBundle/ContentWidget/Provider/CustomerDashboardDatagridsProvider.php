<?php

namespace Oro\Bundle\CommerceBundle\ContentWidget\Provider;

/**
 * Provides pairs [<label> => <datagrid Name>] of storefront datagrids for content widget.
 */
class CustomerDashboardDatagridsProvider implements CustomerDashboardDatagridsProviderInterface
{
    private const array DATAGRIDS = [
        'oro.customer.frontend.dashboard.widgets.my_latest_orders.title' =>
            'frontend-customer-dashboard-my-latest-orders-grid',
        'oro.customer.frontend.dashboard.widgets.open_quotes.title' =>
            'frontend-customer-dashboard-open-quotes-grid',
        'oro.customer.frontend.dashboard.widgets.my_checkouts.title' =>
            'frontend-customer-dashboard-my-checkouts-grid',
        'oro.customer.frontend.dashboard.widgets.latest_rfqs.title' =>
            'frontend-customer-dashboard-requests-for-quote-grid',
        'oro.customer.frontend.dashboard.widgets.my_shopping_lists.title' =>
            'frontend-customer-dashboard-my-shopping-lists-grid'
    ];

    private array $datagrids = [];

    public function setDatagrids(array $datagrids): void
    {
        $this->datagrids = $datagrids;
    }

    public function getDatagrids(): array
    {
        return \array_merge(self::DATAGRIDS, $this->datagrids);
    }
}
