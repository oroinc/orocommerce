<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\ShippingBundle\Datagrid\ShippingRuleActionsVisibilityProvider;

class ShippingRuleActionsVisibilityProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ShippingRuleActionsVisibilityProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->provider = new ShippingRuleActionsVisibilityProvider();
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
     * @dataProvider recordsDataProvider
     */
    public function testGetActionsVisibility(ResultRecordInterface $record, array $actions, array $expected)
    {
        $this->assertEquals(
            $expected,
            $this->provider->getActionsVisibility($record, $actions)
        );
    }

    /**
     * @return array
     */
    public function recordsDataProvider()
    {
        return [
            'enabled' => [
                new ResultRecord(['enabled' => true]),
                ['enable' => ['config'], 'disable' => ['config']],
                ['enable' => false, 'disable' => true]
            ],
            'disabled' => [
                new ResultRecord(['enabled' => false]),
                ['enable' => ['config'], 'disable' => ['config']],
                ['enable' => true, 'disable' => false]
            ]
        ];
    }
}
