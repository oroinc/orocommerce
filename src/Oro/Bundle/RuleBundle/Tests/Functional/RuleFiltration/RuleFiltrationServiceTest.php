<?php

namespace Oro\Bundle\RuleBundle\Tests\Functional\RuleFiltration;

use Oro\Bundle\RuleBundle\Entity\RuleInterface;
use Oro\Bundle\RuleBundle\Entity\RuleOwnerInterface;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class RuleFiltrationServiceTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
    }

    private function createRuleOwner(RuleInterface $rule): RuleOwnerInterface
    {
        $ruleOwner = $this->createMock(RuleOwnerInterface::class);
        $ruleOwner->expects($this->any())
            ->method('getRule')
            ->willReturn($rule);

        return $ruleOwner;
    }

    private function createRule(
        bool $isEnabled,
        string $expression,
        int $sortOrder,
        bool $isStopProcessing
    ): RuleInterface {
        $rule = $this->createMock(RuleInterface::class);
        $rule->expects($this->any())
            ->method('isEnabled')
            ->willReturn($isEnabled);
        $rule->expects($this->any())
            ->method('getExpression')
            ->willReturn($expression);
        $rule->expects($this->once())
            ->method('getSortOrder')
            ->willReturn($sortOrder);
        $rule->expects($this->once())
            ->method('isStopProcessing')
            ->willReturn($isStopProcessing);

        return $rule;
    }

    /**
     * @dataProvider ruleFiltrationProvider
     */
    public function testRuleFiltration(array $context, array $ruleOwners, array $expectedFilteredRules)
    {
        // this actually gets the decorated oro_rule.rule_filtration.service which cannot be accessed directly
        // because of privacy "public = false"
        $ruleFiltrationService = $this->getContainer()->get('oro_rule.rule_filtration.enabled_decorator');

        $actualFilteredRules = $ruleFiltrationService->getFilteredRuleOwners($ruleOwners, $context);

        $this->assertEquals($expectedFilteredRules, $actualFilteredRules);
    }

    public function ruleFiltrationProvider(): array
    {
        return [
            'not empty result' => [
                'context' => [
                    'someObject' => [
                        'property1' => 'value1',
                        'property2' => 'value2',
                    ],
                ],
                'ruleOwners' => [
                    $ruleOwnerOne = $this->createRuleOwner(
                        $this->createRule(true, 'someObject.property1 = \'value1\'', 1, false)
                    ),
                    $ruleOwnerTwo = $this->createRuleOwner(
                        $this->createRule(true, 'someObject.property2 = \'value2\'', 2, false)
                    ),
                ],
                'expectedResult' => [
                    $ruleOwnerOne,
                    $ruleOwnerTwo,
                ],
            ],
            'empty result' => [
                'context' => [],
                'ruleOwners' => [
                    $this->createRuleOwner(
                        $this->createRule(true, 'someObject.property1 = \'value1\'', 1, false)
                    ),
                    $this->createRuleOwner(
                        $this->createRule(true, 'someObject.property2 = \'value2\'', 2, false)
                    ),
                ],
                'expectedResult' => [
                ],
            ],
        ];
    }
}
