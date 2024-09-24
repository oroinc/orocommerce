<?php

namespace Oro\Bundle\RuleBundle\Tests\Unit\RuleFiltration;

use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\RuleBundle\Entity\RuleInterface;
use Oro\Bundle\RuleBundle\Entity\RuleOwnerInterface;
use Oro\Bundle\RuleBundle\RuleFiltration\ExpressionLanguageRuleFiltrationService;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;
use Oro\Component\ExpressionLanguage\ExpressionLanguage;
use Psr\Log\LoggerInterface;

class ExpressionLanguageRuleFiltrationServiceTest extends \PHPUnit\Framework\TestCase
{
    private const EXPRESSION_VARIABLE = 'test';
    private const EXPRESSION_VALUE = 1;

    /** @var RuleFiltrationServiceInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $baseFiltrationService;

    /** @var ExpressionLanguageRuleFiltrationService */
    private $filtrationService;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    #[\Override]
    protected function setUp(): void
    {
        $this->baseFiltrationService = $this->createMock(RuleFiltrationServiceInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->filtrationService = new ExpressionLanguageRuleFiltrationService(
            $this->baseFiltrationService,
            new ExpressionLanguage(),
            $this->logger
        );
    }

    private function getRule(string $name, ?string $expression): Rule
    {
        $rule = new Rule();
        $rule->setName($name);
        if (null !== $expression) {
            $rule->setExpression($expression);
        }

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
     * @dataProvider ruleOwnersDataProvider
     */
    public function testGetFilteredRuleOwners(array $ruleOwners, array $expectedRuleOwners): void
    {
        $context = [self::EXPRESSION_VARIABLE => self::EXPRESSION_VALUE];

        $this->baseFiltrationService->expects(self::once())
            ->method('getFilteredRuleOwners')
            ->with($expectedRuleOwners, $context)
            ->willReturn($expectedRuleOwners);

        self::assertEquals(
            $expectedRuleOwners,
            $this->filtrationService->getFilteredRuleOwners($ruleOwners, $context)
        );
    }

    public function ruleOwnersDataProvider(): array
    {
        $applicable = $this->getRuleOwner($this->getRule(
            '1',
            self::EXPRESSION_VARIABLE . ' = ' . self::EXPRESSION_VALUE
        ));
        $notApplicable = $this->getRuleOwner($this->getRule(
            '2',
            self::EXPRESSION_VARIABLE . ' > ' . self::EXPRESSION_VALUE
        ));
        $nullExpressionOwner = $this->getRuleOwner($this->getRule('3', null));
        $exceptionOwner = $this->getRuleOwner($this->getRule('4', 't = %'));
        $typeErrorOwner = $this->getRuleOwner($this->getRule('5', 't in (%)'));

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
            ]
        ];
    }

    public function testLogError(): void
    {
        $context = [self::EXPRESSION_VARIABLE => self::EXPRESSION_VALUE];

        $exceptionOwner = $this->getRuleOwner($this->getRule('testRule', 't = %'));

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

        $this->baseFiltrationService->expects(self::once())
            ->method('getFilteredRuleOwners')
            ->with([], $context)
            ->willReturn([]);

        self::assertSame([], $this->filtrationService->getFilteredRuleOwners([$exceptionOwner], $context));
    }
}
