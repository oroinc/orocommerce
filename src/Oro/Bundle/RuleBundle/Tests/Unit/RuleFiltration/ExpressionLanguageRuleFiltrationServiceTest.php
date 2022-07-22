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

    /** @var RuleFiltrationServiceInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $service;

    /** @var ExpressionLanguageRuleFiltrationServiceDecorator */
    private $serviceDecorator;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    protected function setUp(): void
    {
        $this->service = $this->createMock(RuleFiltrationServiceInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

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

        $this->service->expects(self::once())
            ->method('getFilteredRuleOwners')
            ->with($expectedRuleOwners, $context)
            ->willReturn($expectedRuleOwners);

        $actualRuleOwners = $this->serviceDecorator->getFilteredRuleOwners($ruleOwners, $context);

        self::assertEquals($expectedRuleOwners, $actualRuleOwners);
    }

    public function ruleOwnersDataProvider(): array
    {
        $applicable = $this->createApplicableOwner('1');
        $notApplicable = $this->createNotApplicableOwner('2');
        $exceptionOwner = $this->createExceptionOwner();
        $typeErrorOwner = $this->createTypeErrorOwner();
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
            'testWithTypeErrorLanguageExpression' => [
                [$applicable, $typeErrorOwner, $applicable],
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

        $this->logger->expects(self::once())
            ->method('error')
            ->with(
                'Rule condition evaluation error: {error}. ' .
                '{rule_owner_class_name} with name "{rule_name}" was skipped.',
                [
                    'expression' => $exceptionOwner->getRule()->getExpression(),
                    'values' => $context,
                    'rule_owner' => $exceptionOwner,
                    'error' => 'Unexpected token "operator" of value "%" around position 5 for expression `t = %`.',
                    'rule_owner_class_name' => get_class($exceptionOwner),
                    'rule_name' => $exceptionOwner->getRule()->getName()
                ]
            );

        $this->service->expects(self::once())
            ->method('getFilteredRuleOwners')
            ->with([], $context)
            ->willReturn([]);

        self::assertEmpty($this->serviceDecorator->getFilteredRuleOwners([$exceptionOwner], $context));
    }

    private function createNotApplicableOwner(string $name): RuleOwnerInterface
    {
        $rule = new Rule();
        $rule
            ->setName($name)
            ->setExpression(self::EXPRESSION_VARIABLE . ' > ' . self::EXPRESSION_VALUE);

        return $this->createRuleOwner($rule);
    }

    private function createApplicableOwner(string $name): RuleOwnerInterface
    {
        $rule = new Rule();
        $rule
            ->setName($name)
            ->setExpression(self::EXPRESSION_VARIABLE . ' = ' . self::EXPRESSION_VALUE);

        return $this->createRuleOwner($rule);
    }

    private function createNullExpressionOwner(string $name): RuleOwnerInterface
    {
        $rule = new Rule();
        $rule->setName($name);

        return $this->createRuleOwner($rule);
    }

    private function createExceptionOwner(): RuleOwnerInterface
    {
        $rule = new Rule();
        $rule->setExpression('t = %');

        return $this->createRuleOwner($rule);
    }

    private function createTypeErrorOwner(): RuleOwnerInterface
    {
        $rule = new Rule();
        $rule->setExpression('t in (%)');

        return $this->createRuleOwner($rule);
    }

    private function createRuleOwner(RuleInterface $rule): RuleOwnerInterface
    {
        $ruleOwner = $this->createMock(RuleOwnerInterface::class);
        $ruleOwner->expects(self::any())
            ->method('getRule')
            ->willReturn($rule);

        return $ruleOwner;
    }
}
