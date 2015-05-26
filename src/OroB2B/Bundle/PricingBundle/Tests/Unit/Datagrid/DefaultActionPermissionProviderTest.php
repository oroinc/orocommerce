<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;

use OroB2B\Bundle\PricingBundle\Datagrid\DefaultActionPermissionProvider;

class DefaultActionPermissionProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DefaultActionPermissionProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->provider = new DefaultActionPermissionProvider();
    }

    protected function tearDown()
    {
        unset($this->provider);
    }

    /**
     * @param ResultRecordInterface $record
     * @param array $actions
     * @param array $expected
     *
     * @dataProvider permissionsDataProvider
     */
    public function testGetPermissions(ResultRecordInterface $record, array $actions, array $expected)
    {
        $this->assertEquals(
            $expected,
            $this->provider->getPermissions($record, $actions)
        );
    }

    /**
     * @return array
     */
    public function permissionsDataProvider()
    {
        return [
            'without action' => [
                new ResultRecord(['is_default' => true]),
                ['some_action' => ['config']],
                ['some_action' => true]
            ],
            'already default' => [
                new ResultRecord(['is_default' => true]),
                ['some_action' => ['config'], 'default' => ['config']],
                ['some_action' => true, 'default' => false]
            ],
            'set default allowed' => [
                new ResultRecord(['is_default' => false]),
                ['some_action' => ['config'], 'default' => ['config']],
                ['some_action' => true, 'default' => true]
            ]
        ];
    }
}
