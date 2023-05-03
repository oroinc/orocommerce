<?php

namespace Oro\Bundle\RuleBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\RuleBundle\Datagrid\RuleActionsVisibilityProvider;
use Oro\Bundle\RuleBundle\Entity\Rule;

class RuleActionsVisibilityProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var RuleActionsVisibilityProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->provider = new RuleActionsVisibilityProvider();
    }

    /**
     * @dataProvider recordsDataProvider
     */
    public function testGetActionsVisibility(bool $enabled, array $actions, array $expected)
    {
        $rule = $this->createMock(Rule::class);
        $rule->expects(self::any())
            ->method('isEnabled')
            ->willReturn($enabled);
        $this->assertEquals(
            $expected,
            $this->provider->getActionsVisibility(new ResultRecord(['rule' => $rule]), $actions)
        );
    }

    public function recordsDataProvider(): array
    {
        return [
            'enabled' => [
                true,
                ['enable' => ['config'], 'disable' => ['config']],
                ['enable' => false, 'disable' => true],
            ],
            'disabled' => [
                false,
                ['enable' => ['config'], 'disable' => ['config']],
                ['enable' => true, 'disable' => false],
            ],
        ];
    }
}
