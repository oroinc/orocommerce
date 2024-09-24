<?php

namespace Oro\Bundle\RuleBundle\Tests\Unit\RuleFiltration;

use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\RuleBundle\Entity\RuleInterface;
use Oro\Bundle\RuleBundle\Entity\RuleOwnerInterface;
use Oro\Bundle\RuleBundle\RuleFiltration\EnabledRuleFiltrationService;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;

class EnabledRuleFiltrationServiceTest extends \PHPUnit\Framework\TestCase
{
    /** @var RuleFiltrationServiceInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $baseFiltrationService;

    /** @var EnabledRuleFiltrationService */
    private $filtrationService;

    #[\Override]
    protected function setUp(): void
    {
        $this->baseFiltrationService = $this->createMock(RuleFiltrationServiceInterface::class);

        $this->filtrationService = new EnabledRuleFiltrationService($this->baseFiltrationService);
    }

    private function getRule(bool $enabled): Rule
    {
        $rule = new Rule();
        $rule->setEnabled($enabled);

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

    /**
     * @dataProvider getFilteredRuleOwnersDataProvider
     */
    public function testGetFilteredRuleOwners(array $ruleOwners, array $expectedRuleOwners): void
    {
        $context = [];

        $this->baseFiltrationService->expects(self::once())
            ->method('getFilteredRuleOwners')
            ->with($expectedRuleOwners, $context)
            ->willReturn($expectedRuleOwners);

        self::assertEquals(
            $expectedRuleOwners,
            $this->filtrationService->getFilteredRuleOwners($ruleOwners, $context)
        );
    }

    public function getFilteredRuleOwnersDataProvider(): array
    {
        $ownerEnabledRule = $this->getRuleOwner($this->getRule(true));
        $ownerDisabledRule = $this->getRuleOwner($this->getRule(false));

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
}
