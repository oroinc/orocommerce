<?php

namespace Oro\Bundle\RuleBundle\Tests\Functional\RuleFiltration;

use Oro\Bundle\RuleBundle\Entity\RuleInterface;
use Oro\Bundle\RuleBundle\Entity\RuleOwnerInterface;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class RuleFiltrationServiceTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient();
    }

    /**
     * @param RuleInterface $rule
     *
     * @return RuleOwnerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createRuleOwnerMock(RuleInterface $rule)
    {
        $ruleOwnerMock = $this->createMock(RuleOwnerInterface::class);

        $ruleOwnerMock
            ->expects($this->any())
            ->method('getRule')
            ->willReturn($rule);

        return $ruleOwnerMock;
    }

    /**
     * @param bool $isEnabled
     * @param string $expression
     * @param int $sortOrder
     * @param bool $isStopProcessing
     *
     * @return RuleInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createRuleMock($isEnabled, $expression, $sortOrder, $isStopProcessing)
    {
        $ruleMock = $this->createMock(RuleInterface::class);

        $ruleMock
            ->expects($this->any())
            ->method('isEnabled')
            ->willReturn($isEnabled);

        $ruleMock
            ->expects($this->any())
            ->method('getExpression')
            ->willReturn($expression);

        $ruleMock
            ->expects($this->once())
            ->method('getSortOrder')
            ->willReturn($sortOrder);

        $ruleMock
            ->expects($this->once())
            ->method('isStopProcessing')
            ->willReturn($isStopProcessing);

        return $ruleMock;
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

    /**
     * @return array
     */
    public function ruleFiltrationProvider()
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
                    $ruleOwnerOne = $this->createRuleOwnerMock(
                        $this->createRuleMock(true, 'someObject.property1 = \'value1\'', 1, false)
                    ),
                    $ruleOwnerTwo = $this->createRuleOwnerMock(
                        $this->createRuleMock(true, 'someObject.property2 = \'value2\'', 2, false)
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
                    $this->createRuleOwnerMock(
                        $this->createRuleMock(true, 'someObject.property1 = \'value1\'', 1, false)
                    ),
                    $this->createRuleOwnerMock(
                        $this->createRuleMock(true, 'someObject.property2 = \'value2\'', 2, false)
                    ),
                ],
                'expectedResult' => [
                ],
            ],
        ];
    }
}
