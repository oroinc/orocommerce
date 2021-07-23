<?php

namespace Oro\Bundle\RuleBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\RuleBundle\Validator\Constraints\ExpressionLanguageSyntaxValidator;
use Oro\Component\ExpressionLanguage\BasicExpressionLanguageValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ExpressionLanguageSyntaxValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var BasicExpressionLanguageValidator|\PHPUnit\Framework\MockObject\MockObject
     */
    private $basicExpressionLanguageValidator;

    /**
     * @var ExpressionLanguageSyntaxValidator
     */
    private $expressionLanguageSyntaxValidator;

    /**
     * @var ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $context;

    protected function setUp(): void
    {
        $this->basicExpressionLanguageValidator = $this->createMock(BasicExpressionLanguageValidator::class);
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->expressionLanguageSyntaxValidator = new ExpressionLanguageSyntaxValidator(
            $this->basicExpressionLanguageValidator
        );
    }

    public function testWithoutExpression()
    {
        $constraint = $this->createMock(Constraint::class);
        $this->basicExpressionLanguageValidator
            ->expects(static::never())
            ->method('validate');
        $this->context
            ->expects(static::never())
            ->method('addViolation');
        $this->expressionLanguageSyntaxValidator->validate('', $constraint);
    }

    /**
     * @dataProvider expressionsProvider
     *
     * @param string $expression
     * @param string $message
     */
    public function testValidate($expression, $message)
    {
        $this->basicExpressionLanguageValidator
            ->expects(static::once())
            ->method('validate')
            ->with($expression)
            ->willReturn($message);
        $this->context
            ->expects(static::any())
            ->method('addViolation')
            ->with($message);

        /** @var Constraint|\PHPUnit\Framework\MockObject\MockObject $constraint * */
        $constraint = $this->createMock(Constraint::class);
        $this->expressionLanguageSyntaxValidator->initialize($this->context);
        $this->expressionLanguageSyntaxValidator->validate($expression, $constraint);
    }

    public function expressionsProvider(): array
    {
        return [
            'non valid 1' => [
                '=true',
                'Unexpected character "=" around position 0.',
            ],
            'non valid 2' => [
                'some()',
                'The function "some" does not exist around position 1.',
            ],
            'non valid 3' => [
                'd=-=b',
                'Unexpected token "operator" of value "=" around position 4.',
            ],
            'valid' => [
                'currency != "USD"',
                '',
            ],
        ];
    }
}
