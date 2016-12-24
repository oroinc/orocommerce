<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\PaymentBundle\Datagrid\PaymentRuleActionsVisibilityProvider;

class PaymentRuleActionsVisibilityProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PaymentRuleActionsVisibilityProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->provider = new PaymentRuleActionsVisibilityProvider();
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
