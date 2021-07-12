<?php

namespace Oro\Bundle\RuleBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\RuleBundle\Validator\Constraints\ExpressionLanguageSyntaxValidator;
use Oro\Component\ExpressionLanguage\BasicExpressionLanguageValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ExpressionLanguageSyntaxValidatorTest extends ConstraintValidatorTestCase
{
    /** @var BasicExpressionLanguageValidator|\PHPUnit\Framework\MockObject\MockObject */
    private $basicExpressionLanguageValidator;

    protected function setUp(): void
    {
        $this->basicExpressionLanguageValidator = $this->createMock(BasicExpressionLanguageValidator::class);
        parent::setUp();
    }

    protected function createValidator()
    {
        return new ExpressionLanguageSyntaxValidator($this->basicExpressionLanguageValidator);
    }

    public function testWithoutExpression()
    {
        $this->basicExpressionLanguageValidator->expects(self::never())
            ->method('validate');

        $constraint = $this->createMock(Constraint::class);
        $this->validator->validate('', $constraint);

        $this->assertNoViolation();
    }

    /**
     * @dataProvider expressionsProvider
     */
    public function testValidate(string $expression, string $message)
    {
        $this->basicExpressionLanguageValidator->expects(self::once())
            ->method('validate')
            ->with($expression)
            ->willReturn($message);

        $constraint = $this->createMock(Constraint::class);
        $this->validator->validate($expression, $constraint);

        if ($message) {
            $this->buildViolation($message)
                ->assertRaised();
        } else {
            $this->assertNoViolation();
        }
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
