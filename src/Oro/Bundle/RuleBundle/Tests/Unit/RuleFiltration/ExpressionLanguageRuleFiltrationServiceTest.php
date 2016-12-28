<?php

namespace Oro\Bundle\RuleBundle\Tests\Unit\RuleFiltration;

use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\RuleBundle\Entity\RuleOwnerInterface;
use Oro\Bundle\RuleBundle\RuleFiltration\ExpressionLanguageRuleFiltrationServiceDecorator;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;
use Psr\Log\LoggerInterface;

class ExpressionLanguageRuleFiltrationServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @internal
     */
    const EXPRESSION_VARIABLE = 'test';

    /**
     * @internal
     */
    const EXPRESSION_VALUE = 1;

    /**
     * @var RuleFiltrationServiceInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $service;

    /**
     * @var ExpressionLanguageRuleFiltrationServiceDecorator
     */
    private $serviceDecorator;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    protected function setUp()
    {
        $this->service = $this->getMockBuilder(RuleFiltrationServiceInterface::class)
            ->setMethods(['getFilteredRuleOwners'])->getMockForAbstractClass();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->setMethods(['error'])->getMockForAbstractClass();
        $this->serviceDecorator = new ExpressionLanguageRuleFiltrationServiceDecorator($this->service, $this->logger);
    }

    /**
     * @dataProvider ruleOwnersDataProvider
     *
     * @param RuleOwnerInterface[]|array $ruleOwners
     * @param RuleOwnerInterface[]|array $expectedRuleOwners
     */
    public function testGetFilteredRuleOwners(array $ruleOwners, array $expectedRuleOwners)
    {
        $context = [self::EXPRESSION_VARIABLE => self::EXPRESSION_VALUE];

        $this->service->expects(static::once())
            ->method('getFilteredRuleOwners')
            ->with($expectedRuleOwners, $context)
            ->willReturn($expectedRuleOwners);

        $actualRuleOwners = $this->serviceDecorator->getFilteredRuleOwners($ruleOwners, $context);

        static::assertEquals($expectedRuleOwners, $actualRuleOwners);
    }

    /**
     * @return array
     */
    public function ruleOwnersDataProvider()
    {
        $applicable = $this->createApplicableOwner('1');
        $notApplicable = $this->createNotApplicableOwner('2');
        $exceptionOwner = $this->createExceptionOwner();
        $nullExpressionOwner = $this->createNullExpressionOwner('3');

        return [
            'testOnlyApplicablePass' => [
                [$applicable, $notApplicable, $applicable],
                [$applicable, $applicable]
            ],
            'testWithWrongLanguageExpression' => [
                [$applicable, $exceptionOwner, $applicable],
                [$applicable, $applicable]
            ],
            'testWithNullLanguageExpression' => [
                [$nullExpressionOwner, $notApplicable, $applicable],
                [$nullExpressionOwner, $applicable]
            ],
        ];
    }

    public function testLogError()
    {
        $context = [self::EXPRESSION_VARIABLE => self::EXPRESSION_VALUE];

        $rule = new Rule();
        $rule->setExpression('t = %');

        $exceptionOwner = $this->createRuleOwner($rule);

        $this->logger->expects(static::once())
            ->method('error')
            ->with('Rule condition evaluation error: Unexpected token "operator" of value "%" around position 5.', [
                'expression' => $exceptionOwner->getRule()->getExpression(),
                'values' => $context,
            ]);

        $this->service->expects(static::once())
            ->method('getFilteredRuleOwners')
            ->with([], $context)
            ->willReturn([]);

        $this->assertEmpty($this->serviceDecorator->getFilteredRuleOwners([$exceptionOwner], $context));
    }

    /**
     * @param string $name
     *
     * @return RuleOwnerInterface
     */
    private function createNotApplicableOwner($name)
    {
        $rule = new Rule();

        $rule->setName($name)
            ->setExpression(self::EXPRESSION_VARIABLE . ' > ' . self::EXPRESSION_VALUE);

        return $this->createRuleOwner($rule);
    }

    /**
     * @param string $name
     *
     * @return RuleOwnerInterface
     */
    private function createApplicableOwner($name)
    {
        $rule = new Rule();

        $rule->setName($name)
            ->setExpression(self::EXPRESSION_VARIABLE . ' = ' . self::EXPRESSION_VALUE);

        return $this->createRuleOwner($rule);
    }

    /**
     * @param string $name
     *
     * @return RuleOwnerInterface
     */
    private function createNullExpressionOwner($name)
    {
        $rule = new Rule();

        $rule->setName($name);

        return $this->createRuleOwner($rule);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|RuleOwnerInterface
     */
    private function createExceptionOwner()
    {
        $rule = new Rule();

        $rule->setExpression('t = %');

        return $this->createRuleOwner($rule);
    }

    /**
     * @param Rule $rule
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|RuleOwnerInterface
     */
    private function createRuleOwner(Rule $rule)
    {
        $ruleOwner = $this->createPartialMock(RuleOwnerInterface::class, ['getRule']);
        $ruleOwner->expects(static::any())
            ->method('getRule')
            ->willReturn($rule);

        return $ruleOwner;
    }
}
