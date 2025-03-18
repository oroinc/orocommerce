<?php

namespace Oro\Bundle\CommerceBundle\Tests\Unit\ContentWidget\Provider;

use Oro\Bundle\CommerceBundle\ContentWidget\Provider\CustomerDashboardDatagridsProvider;
use PHPUnit\Framework\TestCase;

final class CustomerDashboardDatagridsProviderTest extends TestCase
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

    public function testGetDatagrids(): void
    {
        $provider = new CustomerDashboardDatagridsProvider();

        self::assertSame(self::DATAGRIDS, $provider->getDatagrids());
    }

    public function testSetDatagrids(): void
    {
        $provider = new CustomerDashboardDatagridsProvider();
        $provider->setDatagrids(['oro.test.frontend.datagrid' => 'frontend-test-datagrid']);

        self::assertSame(
            \array_merge(self::DATAGRIDS, ['oro.test.frontend.datagrid' => 'frontend-test-datagrid']),
            $provider->getDatagrids()
        );
    }
}
