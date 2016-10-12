<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\PricingBundle\Validator\Constraints\PriceRuleExpression;
use Oro\Bundle\PricingBundle\Validator\Constraints\PriceRuleExpressionValidator;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\Expression\ExpressionLanguageConverter;
use Oro\Component\Expression\ExpressionParser;
use Oro\Component\Expression\FieldsProviderInterface;
use Oro\Component\Expression\Preprocessor\ExpressionPreprocessorInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class PriceRuleExpressionValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ExpressionParser
     */
    protected $parser;

    /**
     * @var ExpressionPreprocessorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $preprocessor;

    /**
     * @var FieldsProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $fieldsProvider;

    /**
     * @var PriceRuleExpressionValidator
     */
    protected $expressionValidator;

    /**
     * @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    protected function setUp()
    {
        $this->fieldsProvider = $this->getMock(FieldsProviderInterface::class);
        $this->preprocessor = $this->getMock(ExpressionPreprocessorInterface::class);
        $expressionConverter = new ExpressionLanguageConverter($this->fieldsProvider);
        $this->parser = new ExpressionParser($expressionConverter);
        $this->parser->addNameMapping('product', Product::class);
        $this->translator = $this->getMock(TranslatorInterface::class);
        $this->expressionValidator = new PriceRuleExpressionValidator(
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

        /** @var ExecutionContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->getMock(ExecutionContextInterface::class);
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

        /** @var ExecutionContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->getMock(ExecutionContextInterface::class);
        $context->expects($this->once())->method('addViolation');

        $this->doTestValidation($value, $context);
    }

    public function testValidateAdditionalField()
    {
        $constraint = new PriceRuleExpression();
        $constraint->allowedFields = [
            Product::class => ['additionalField']
        ];
        $constraint->fieldLabel = 'Field label';

        $this->fieldsProvider->method('getFields')->willReturn(['fieldKnown']);

        /** @var ExecutionContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->getMock(ExecutionContextInterface::class);
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

        /** @var ExecutionContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->getMock(ExecutionContextInterface::class);
        $context->expects($this->once())
            ->method('addViolation')
            ->with('oro.pricing.validators.division_by_zero.message');

        $this->doTestValidation($value, $context);
    }

    /**
     * @param string $value
     * @param ExecutionContextInterface $context
     */
    protected function doTestValidation($value, ExecutionContextInterface $context)
    {
        $constraint = new PriceRuleExpression();
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
            ['', []],
            [null, []],
            ['product.msrp.value + 1', ['value']],
        ];
    }

    /**
     * @return array
     */
    public function validateErrorDataProvider()
    {
        return [
            ['xxx', []],
            ['product.sku == SKU"', ['sku', 'msrp']],
            ['product.msrp.value + 1', []],
        ];
    }
}
