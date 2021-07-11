<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Validator\Constraints\Expression;
use Oro\Bundle\ProductBundle\Validator\Constraints\ExpressionValidator;
use Oro\Component\Expression\ExpressionLanguageConverter;
use Oro\Component\Expression\ExpressionParser;
use Oro\Component\Expression\FieldsProviderInterface;
use Oro\Component\Expression\Preprocessor\ExpressionPreprocessorInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExpressionValidatorTest extends ConstraintValidatorTestCase
{
    /** @var ExpressionParser */
    private $parser;

    /** @var ExpressionPreprocessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $preprocessor;

    /** @var FieldsProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $fieldsProvider;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    protected function setUp(): void
    {
        $this->fieldsProvider = $this->createMock(FieldsProviderInterface::class);
        $this->preprocessor = $this->createMock(ExpressionPreprocessorInterface::class);
        $this->parser = new ExpressionParser(new ExpressionLanguageConverter($this->fieldsProvider));
        $this->parser->addNameMapping('product', Product::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        parent::setUp();
    }

    protected function createValidator()
    {
        return new ExpressionValidator(
            $this->parser,
            $this->preprocessor,
            $this->fieldsProvider,
            $this->translator
        );
    }

    /**
     * @dataProvider validateSuccessDataProvider
     */
    public function testValidateSuccess(?string $value, array $attributes)
    {
        $this->fieldsProvider->expects($this->any())
            ->method('getFields')
            ->willReturn($attributes);

        $this->preprocessor->expects($this->any())
            ->method('process')
            ->with($value)
            ->willReturnArgument(0);

        $constraint = new Expression();
        $constraint->fieldLabel = 'Field label';
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    public function validateSuccessDataProvider(): array
    {
        return [
            'Empty string' => ['', []],
            'Null' => [null, []],
            'Valid formula' => ['product.msrp.value + 1', ['value']],
            'Syntax error 1' => ['xxx', []],
            'Syntax error 2' => ['product.sku == SKU"', ['sku', 'msrp']],
        ];
    }

    public function testValidateForUnsupportedField()
    {
        $value = 'product.msrp.value + 1';

        $this->fieldsProvider->expects($this->any())
            ->method('getFields')
            ->willReturn([]);

        $this->preprocessor->expects($this->any())
            ->method('process')
            ->with($value)
            ->willReturnArgument(0);

        $constraint = new Expression();
        $constraint->fieldLabel = 'Field label';
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->messageAs)
            ->setParameters(['%inputName%' => null, '%fieldName%' => 'value'])
            ->assertRaised();
    }

    public function testValidateAdditionalField()
    {
        $value = 'product.additionalField';

        $this->fieldsProvider->expects($this->any())
            ->method('getFields')
            ->willReturn(['fieldKnown']);

        $this->preprocessor->expects($this->any())
            ->method('process')
            ->with($value)
            ->willReturnArgument(0);

        $constraint = new Expression();
        $constraint->allowedFields = [Product::class => ['additionalField']];
        $constraint->fieldLabel = 'Field label';
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateDivisionByZero()
    {
        $value = 'product.msrp.value/0';

        $this->fieldsProvider->expects($this->any())
            ->method('getFields')
            ->willReturn(['value']);

        $this->preprocessor->expects($this->any())
            ->method('process')
            ->with($value)
            ->willReturnArgument(0);

        $constraint = new Expression();
        $constraint->fieldLabel = 'Field label';
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->divisionByZeroMessage)
            ->assertRaised();
    }
}
