<?php

namespace Oro\Bundle\RuleBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\RuleBundle\Validator\Constraints\ExpressionLanguageSyntaxValidator;
use Symfony\Component\Validator\Constraints\ExpressionLanguageSyntaxValidator as SymfonyExpressionLanguageValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ExpressionLanguageSyntaxValidatorTest extends ConstraintValidatorTestCase
{
    private SymfonyExpressionLanguageValidator|\PHPUnit\Framework\MockObject\MockObject $innerValidator;

    protected function setUp(): void
    {
        $this->innerValidator = $this->createMock(SymfonyExpressionLanguageValidator::class);

        parent::setUp();
    }

    protected function createValidator(): ExpressionLanguageSyntaxValidator
    {
        return new ExpressionLanguageSyntaxValidator($this->innerValidator);
    }

    public function testValidateDoesNothingWhenNoExpression(): void
    {
        $this->innerValidator
            ->expects(self::never())
            ->method('validate');

        $this->validator->validate('', $this->constraint);

        $this->assertNoViolation();
    }

    public function testValidateDoesNothingWhenNoExpressionAndSpaces(): void
    {
        $this->innerValidator
            ->expects(self::never())
            ->method('validate');

        $this->validator->validate('  ', $this->constraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenNumericExpression(): void
    {
        $this->innerValidator
            ->expects(self::once())
            ->method('validate')
            ->with('42');

        $this->validator->validate(42, $this->constraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenExpressionHasExtraSpaces(): void
    {
        $this->innerValidator
            ->expects(self::once())
            ->method('validate')
            ->with('with extra spaces');

        $this->validator->validate('  with extra spaces  ', $this->constraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenNullExpression(): void
    {
        $this->innerValidator
            ->expects(self::never())
            ->method('validate');

        $this->validator->validate(null, $this->constraint);

        $this->assertNoViolation();
    }

    public function testValidate(): void
    {
        $this->innerValidator
            ->expects(self::once())
            ->method('validate')
            ->with('42+21');

        $this->validator->validate('42+21', $this->constraint);
    }
}
