<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Validator\Constraints\Expression;
use Oro\Bundle\ProductBundle\Validator\Constraints\ExpressionValidator;
use Oro\Component\Expression\ExpressionLanguageConverter;
use Oro\Component\Expression\ExpressionParser;
use Oro\Component\Expression\FieldsProviderInterface;
use Oro\Component\Expression\Preprocessor\ExpressionPreprocessorInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExpressionValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ExpressionParser
     */
    protected $parser;

    /**
     * @var ExpressionPreprocessorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $preprocessor;

    /**
     * @var FieldsProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $fieldsProvider;

    /**
     * @var ExpressionValidator
     */
    protected $expressionValidator;

    /**
     * @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $translator;

    protected function setUp(): void
    {
        $this->fieldsProvider = $this->createMock(FieldsProviderInterface::class);
        $this->preprocessor = $this->createMock(ExpressionPreprocessorInterface::class);
        $expressionConverter = new ExpressionLanguageConverter($this->fieldsProvider);
        $this->parser = new ExpressionParser($expressionConverter);
        $this->parser->addNameMapping('product', Product::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->expressionValidator = new ExpressionValidator(
            $this->parser,
            $this->preprocessor,
            $this->fieldsProvider,
            $this->translator
        );
    }

    /**
     * @dataProvider validateSuccessDataProvider
     * @param string $value
     * @param array $attributes
     */
    public function testValidateSuccess($value, array $attributes)
    {
        $this->fieldsProvider->method('getFields')->willReturn($attributes);

        /** @var ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->never())->method('addViolation');

        $this->doTestValidation($value, $context);
    }

    /**
     * @dataProvider validateErrorDataProvider
     * @param string $value
     * @param array $attributes
     */
    public function testValidateError($value, array $attributes)
    {
        $this->fieldsProvider->method('getFields')->willReturn($attributes);

        /** @var ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->once())->method('addViolation');

        $this->doTestValidation($value, $context);
    }

    public function testValidateAdditionalField()
    {
        $constraint = new Expression();
        $constraint->allowedFields = [
            Product::class => ['additionalField']
        ];
        $constraint->fieldLabel = 'Field label';

        $this->fieldsProvider->method('getFields')->willReturn(['fieldKnown']);

        /** @var ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->never())->method('addViolation');

        $value = 'product.additionalField';

        $this->preprocessor->expects($this->any())
            ->method('process')
            ->with($value)
            ->willReturnArgument(0);

        $this->expressionValidator->initialize($context);
        $this->expressionValidator->validate($value, $constraint);
    }

    public function testValidateDivisionByZero()
    {
        $value = 'product.msrp.value/0';

        $this->fieldsProvider->method('getFields')->willReturn(['value']);

        /** @var ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->once())
            ->method('addViolation')
            ->with('oro.product.validators.division_by_zero.message');

        $this->doTestValidation($value, $context);
    }

    /**
     * @param string $value
     * @param ExecutionContextInterface $context
     */
    protected function doTestValidation($value, ExecutionContextInterface $context)
    {
        $constraint = new Expression();
        $constraint->fieldLabel = 'Field label';
        $this->preprocessor->expects($this->any())
            ->method('process')
            ->with($value)
            ->willReturnArgument(0);
        $this->expressionValidator->initialize($context);
        $this->expressionValidator->validate($value, $constraint);
    }

    /**
     * @return array
     */
    public function validateSuccessDataProvider()
    {
        return [
            'Empty string' => ['', []],
            'Null' => [null, []],
            'Valid formula' => ['product.msrp.value + 1', ['value']],
            'Syntax error 1' => ['xxx', []],
            'Syntax error 2' => ['product.sku == SKU"', ['sku', 'msrp']],
        ];
    }

    /**
     * @return array
     */
    public function validateErrorDataProvider()
    {
        return [
            'Unsupported field' => ['product.msrp.value + 1', []],
        ];
    }
}
