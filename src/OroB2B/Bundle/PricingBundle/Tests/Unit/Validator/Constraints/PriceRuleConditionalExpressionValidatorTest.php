<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\Validator\Context\ExecutionContextInterface;

use OroB2B\Bundle\PricingBundle\Validator\Constraints\PriceRuleConditionalExpressionValidator;

class PriceRuleConditionalExpressionValidatorTest extends AbstractDefinedAttributesValidatorTest
{
    /**
     * @var PriceRuleConditionalExpressionValidator
     */
    protected $expressionValidator;

    protected function setUp()
    {
        parent::setUp();
        $this->expressionValidator = new PriceRuleConditionalExpressionValidator(
            $this->parser,
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

    /**
     * @return array
     */
    public function validateSuccessDataProvider()
    {
        return [
            ['', []],
            [null, []],
            ['0', []],
            ['product.sku == "SKU"', ['sku', 'msrp']],
            ['product.msrp.value == 1', ['value']],
        ];
    }

    /**
     * @return array
     */
    public function validateErrorDataProvider()
    {
        return [
            ['zzz', []],
            ['product.sku = "SKU"', ['sku', 'msrp']],
            ['product.msrp.value == 1', []],
        ];
    }
}
