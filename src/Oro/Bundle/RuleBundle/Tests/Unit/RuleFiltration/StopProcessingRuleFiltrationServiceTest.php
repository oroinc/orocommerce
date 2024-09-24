<?php

namespace Oro\Bundle\RuleBundle\Tests\Unit\RuleFiltration;

use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\RuleBundle\Entity\RuleInterface;
use Oro\Bundle\RuleBundle\Entity\RuleOwnerInterface;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;
use Oro\Bundle\RuleBundle\RuleFiltration\StopProcessingRuleFiltrationService;

class StopProcessingRuleFiltrationServiceTest extends \PHPUnit\Framework\TestCase
{
    /** @var RuleFiltrationServiceInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $baseFiltrationService;

    /** @var StopProcessingRuleFiltrationService */
    private $filtrationService;

    #[\Override]
    protected function setUp(): void
    {
        $this->baseFiltrationService = $this->createMock(RuleFiltrationServiceInterface::class);

        $this->filtrationService = new StopProcessingRuleFiltrationService($this->baseFiltrationService);
    }

    private function getRule(int $sortOrder, bool $stopProcessing): Rule
    {
        $rule = new Rule();
        $rule->setSortOrder($sortOrder);
        $rule->setStopProcessing($stopProcessing);

        return $rule;
    }

    private function getRuleOwner(RuleInterface $rule): RuleOwnerInterface
    {
        $ruleOwner = $this->createMock(RuleOwnerInterface::class);
        $ruleOwner->expects(self::any())
            ->method('getRule')
            ->willReturn($rule);

        return $ruleOwner;
    }

    public function testGetFilteredRuleOwnersSortWithStopProcessing(): void
    {
        $context = [];

        $firstOwnerNonStopProcessingRule = $this->getRuleOwner($this->getRule(1, false));
        $secondOwnerStopProcessingRule = $this->getRuleOwner($this->getRule(2, true));
        $thirdOwnerStopProcessingRule = $this->getRuleOwner($this->getRule(3, true));
        $forthOwnerNonStopProcessingRule = $this->getRuleOwner($this->getRule(4, false));

        $ruleOwners = [
            $forthOwnerNonStopProcessingRule,
            $firstOwnerNonStopProcessingRule,
            $thirdOwnerStopProcessingRule,
            $secondOwnerStopProcessingRule
        ];

        $expectedRuleOwners = [
            $firstOwnerNonStopProcessingRule,
            $secondOwnerStopProcessingRule
        ];

        $this->baseFiltrationService->expects(self::once())
            ->method('getFilteredRuleOwners')
            ->with($expectedRuleOwners, $context)
            ->willReturn($expectedRuleOwners);

        self::assertEquals(
            $expectedRuleOwners,
            $this->filtrationService->getFilteredRuleOwners($ruleOwners, $context)
        );
    }

    public function testGetFilteredRuleOwnersOneNonStopProcessing(): void
    {
        $context = [];

        $ownerNonStopProcessingRule = $this->getRuleOwner($this->getRule(1, false));

        $ruleOwners = [$ownerNonStopProcessingRule];

        $this->baseFiltrationService->expects(self::once())
            ->method('getFilteredRuleOwners')
            ->with($ruleOwners, $context)
            ->willReturn($ruleOwners);

        self::assertEquals(
            $ruleOwners,
            $this->filtrationService->getFilteredRuleOwners($ruleOwners, $context)
        );
    }

    public function testGetFilteredRuleOwnersOneStopProcessing(): void
    {
        $context = [];

        $ownerNonStopProcessingRule = $this->getRuleOwner($this->getRule(1, true));

        $ruleOwners = [$ownerNonStopProcessingRule];

        $this->baseFiltrationService->expects(self::once())
            ->method('getFilteredRuleOwners')
            ->with($ruleOwners, $context)
            ->willReturn($ruleOwners);

        self::assertEquals(
            $ruleOwners,
            $this->filtrationService->getFilteredRuleOwners($ruleOwners, $context)
        );
    }
}
