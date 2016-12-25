<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\RuleBundle\Entity\Rule;
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

    /**
     * @param bool $enabled
     * @param array $actions
     * @param array $expected
     *
     * @dataProvider recordsDataProvider
     */
    public function testGetActionsVisibility($enabled, array $actions, array $expected)
    {
        $rule = $this->createMock(Rule::class);
        $rule->expects(static::any())
            ->method('isEnabled')
            ->willReturn($enabled);
        $this->assertEquals(
            $expected,
            $this->provider->getActionsVisibility(new ResultRecord(['rule' => $rule]), $actions)
        );
    }

    /**
     * @return array
     */
    public function recordsDataProvider()
    {
        return [
            'enabled' => [
                true,
                ['enable' => ['config'], 'disable' => ['config']],
                ['enable' => false, 'disable' => true]
            ],
            'disabled' => [
                false,
                ['enable' => ['config'], 'disable' => ['config']],
                ['enable' => true, 'disable' => false]
            ]
        ];
    }
}
