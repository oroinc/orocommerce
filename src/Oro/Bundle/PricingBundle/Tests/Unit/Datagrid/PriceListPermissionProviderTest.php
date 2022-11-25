<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\PricingBundle\Datagrid\PriceListPermissionProvider;

class PriceListPermissionProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var PriceListPermissionProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->provider = new PriceListPermissionProvider();
    }

    /**
     * @dataProvider permissionsDataProvider
     */
    public function testGetPermissions(ResultRecordInterface $record, array $actions, array $expected)
    {
        $this->assertEquals(
            $expected,
            $this->provider->getPermissions($record, $actions)
        );
    }

    public function permissionsDataProvider(): array
    {
        return [
            'without action' => [
                new ResultRecord(['default' => true]),
                ['some_action' => ['config']],
                ['some_action' => true]
            ],
            'already default' => [
                new ResultRecord(['default' => true]),
                ['some_action' => ['config'], 'default' => ['config'], 'delete' => ['config']],
                ['some_action' => true, 'default' => false, 'delete' => false]
            ],
            'set default allowed' => [
                new ResultRecord(['default' => false]),
                ['some_action' => ['config'], 'default' => ['config'], 'delete' => ['config']],
                ['some_action' => true, 'default' => true, 'delete' => true]
            ]
        ];
    }
}
