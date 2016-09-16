<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\PricingBundle\Expression\Preprocessor\ExpressionPreprocessorInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use Oro\Bundle\PricingBundle\Validator\Constraints\PriceRuleExpression;
use Oro\Bundle\PricingBundle\Expression\ExpressionLanguageConverter;
use Oro\Bundle\PricingBundle\Expression\ExpressionParser;
use Oro\Bundle\PricingBundle\Provider\PriceRuleFieldsProvider;
use Oro\Bundle\PricingBundle\Validator\Constraints\PriceRuleExpressionValidator;
use Oro\Bundle\ProductBundle\Entity\Product;

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
     * @var PriceRuleFieldsProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $fieldsProvider;

    /**
     * @var PriceRuleExpressionValidator
     */
    protected $expressionValidator;

    protected function setUp()
    {
        $this->fieldsProvider = $this->getMockBuilder(PriceRuleFieldsProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->preprocessor = $this->getMock(ExpressionPreprocessorInterface::class);
        $expressionConverter = new ExpressionLanguageConverter($this->fieldsProvider);
        $this->parser = new ExpressionParser($expressionConverter);
        $this->parser->addNameMapping('product', Product::class);
        $this->expressionValidator = new PriceRuleExpressionValidator(
            $this->parser,
            $this->preprocessor,
            $this->fieldsProvider
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

    /**
     * @param string $value
     * @param ExecutionContextInterface $context
     */
    protected function doTestValidation($value, ExecutionContextInterface $context)
    {
        /** @var PriceRuleExpression|\PHPUnit_Framework_MockObject_MockObject $constraint * */
        $constraint = $this->getMockBuilder(PriceRuleExpression::class)
            ->disableOriginalConstructor()
            ->getMock();
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
