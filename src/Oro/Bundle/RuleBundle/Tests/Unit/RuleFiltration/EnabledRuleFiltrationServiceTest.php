<?php

namespace Oro\Bundle\RuleBundle\Tests\Unit\RuleFiltration;

use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\RuleBundle\Entity\RuleInterface;
use Oro\Bundle\RuleBundle\Entity\RuleOwnerInterface;
use Oro\Bundle\RuleBundle\RuleFiltration\EnabledRuleFiltrationServiceDecorator;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;

class EnabledRuleFiltrationServiceTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var RuleFiltrationServiceInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $service;

    /**
     * @var EnabledRuleFiltrationServiceDecorator
     */
    private $serviceDecorator;

    protected function setUp(): void
    {
        $this->service = $this->getMockBuilder(RuleFiltrationServiceInterface::class)
            ->setMethods(['getFilteredRuleOwners'])->getMockForAbstractClass();
        $this->serviceDecorator = new EnabledRuleFiltrationServiceDecorator($this->service);
    }

    /**
     * @dataProvider getFilteredRuleOwnersDataProvider
     * @param RuleOwnerInterface[]|array $ruleOwners
     * @param RuleOwnerInterface[]|array $expectedRuleOwners
     */
    public function testGetFilteredRuleOwners(array $ruleOwners, array $expectedRuleOwners)
    {
        $context = [];
        $this->service->expects(static::once())
            ->method('getFilteredRuleOwners')
            ->with($expectedRuleOwners, $context)
            ->willReturn($expectedRuleOwners);
        $actualShippingRuleOwners = $this->serviceDecorator->getFilteredRuleOwners($ruleOwners, $context);
        static::assertEquals($expectedRuleOwners, $actualShippingRuleOwners);
    }

    /**
     * @return array
     */
    public function getFilteredRuleOwnersDataProvider()
    {
        $enabledRule = (new Rule())->setEnabled(true);
        $disabledRule = (new Rule())->setEnabled(false);

        $ownerEnabledRule = $this->createRuleOwner($enabledRule);
        $ownerDisabledRule = $this->createRuleOwner($disabledRule);

        return [
            'one disabled rule owner' => [
                'ruleOwners' => [$ownerDisabledRule],
                'expectedRuleOwners' => [],
            ],
            'several rule owners' => [
                'ruleOwners' => [$ownerDisabledRule, $ownerEnabledRule, $ownerEnabledRule],
                'expectedRuleOwners' => [$ownerEnabledRule, $ownerEnabledRule],
            ],
            'one enabled rule owner' => [
                'ruleOwners' => [$ownerEnabledRule],
                'expectedRuleOwners' => [$ownerEnabledRule],
            ],
        ];
    }

    /**
     * @param RuleInterface $rule
     *
     * @return RuleOwnerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createRuleOwner(RuleInterface $rule)
    {
        $ruleOwner = $this->createPartialMock(RuleOwnerInterface::class, ['getRule']);
        $ruleOwner->expects(static::any())
            ->method('getRule')
            ->willReturn($rule);

        return $ruleOwner;
    }
}
