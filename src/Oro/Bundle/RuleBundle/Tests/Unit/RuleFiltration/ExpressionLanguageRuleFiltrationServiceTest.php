<?php

namespace Oro\Bundle\RuleBundle\Tests\Unit\RuleFiltration;

use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\RuleBundle\Entity\RuleInterface;
use Oro\Bundle\RuleBundle\Entity\RuleOwnerInterface;
use Oro\Bundle\RuleBundle\RuleFiltration\ExpressionLanguageRuleFiltrationServiceDecorator;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;
use Oro\Component\ExpressionLanguage\ExpressionLanguage;
use Psr\Log\LoggerInterface;

class ExpressionLanguageRuleFiltrationServiceTest extends \PHPUnit\Framework\TestCase
{
    private const EXPRESSION_VARIABLE = 'test';

    private const EXPRESSION_VALUE = 1;

    /**
     * @var RuleFiltrationServiceInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $service;

    /**
     * @var ExpressionLanguageRuleFiltrationServiceDecorator
     */
    private $serviceDecorator;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $logger;

    protected function setUp(): void
    {
        $this->service = $this->getMockBuilder(RuleFiltrationServiceInterface::class)
            ->setMethods(['getFilteredRuleOwners'])->getMockForAbstractClass();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->setMethods(['error'])->getMockForAbstractClass();
        $this->serviceDecorator = new ExpressionLanguageRuleFiltrationServiceDecorator(
            new ExpressionLanguage(),
            $this->service,
            $this->logger
        );
    }

    /**
     * @dataProvider ruleOwnersDataProvider
     *
     * @param RuleOwnerInterface[]|array $ruleOwners
     * @param RuleOwnerInterface[]|array $expectedRuleOwners
     */
    public function testGetFilteredRuleOwners(array $ruleOwners, array $expectedRuleOwners): void
    {
        $context = [self::EXPRESSION_VARIABLE => self::EXPRESSION_VALUE];

        $this->service->expects(static::once())
            ->method('getFilteredRuleOwners')
            ->with($expectedRuleOwners, $context)
            ->willReturn($expectedRuleOwners);

        $actualRuleOwners = $this->serviceDecorator->getFilteredRuleOwners($ruleOwners, $context);

        static::assertEquals($expectedRuleOwners, $actualRuleOwners);
    }

    public function ruleOwnersDataProvider(): array
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

    public function testLogError(): void
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
     * @return \PHPUnit\Framework\MockObject\MockObject|RuleOwnerInterface
     */
    private function createNotApplicableOwner(string $name): \PHPUnit\Framework\MockObject\MockObject
    {
        $rule = new Rule();

        $rule->setName($name)
            ->setExpression(self::EXPRESSION_VARIABLE . ' > ' . self::EXPRESSION_VALUE);

        return $this->createRuleOwner($rule);
    }

    /**
     * @param string $name
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|RuleOwnerInterface
     */
    private function createApplicableOwner(string $name): \PHPUnit\Framework\MockObject\MockObject
    {
        $rule = new Rule();

        $rule->setName($name)
            ->setExpression(self::EXPRESSION_VARIABLE . ' = ' . self::EXPRESSION_VALUE);

        return $this->createRuleOwner($rule);
    }

    /**
     * @param string $name
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|RuleOwnerInterface
     */
    private function createNullExpressionOwner(string $name): \PHPUnit\Framework\MockObject\MockObject
    {
        $rule = new Rule();

        $rule->setName($name);

        return $this->createRuleOwner($rule);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|RuleOwnerInterface
     */
    private function createExceptionOwner(): \PHPUnit\Framework\MockObject\MockObject
    {
        $rule = new Rule();

        $rule->setExpression('t = %');

        return $this->createRuleOwner($rule);
    }

    /**
     * @param RuleInterface $rule
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|RuleOwnerInterface
     */
    private function createRuleOwner(RuleInterface $rule): \PHPUnit\Framework\MockObject\MockObject
    {
        $ruleOwner = $this->createPartialMock(RuleOwnerInterface::class, ['getRule']);
        $ruleOwner->expects(static::any())
            ->method('getRule')
            ->willReturn($rule);

        return $ruleOwner;
    }
}
