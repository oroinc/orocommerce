<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Validator\Constraints;

use OroB2B\Bundle\PricingBundle\Validator\Constraints\DefinedRuleAttributesValidator;

use Symfony\Component\Validator\Context\ExecutionContextInterface;

class DefinedRuleAttributesValidatorTest extends AbstractDefinedAttributesValidatorTest
{
    /**
     * @var DefinedRuleAttributesValidator
     */
    protected $definedAttributesValidator;

    protected function setUp()
    {
        parent::setUp();
        $this->definedAttributesValidator = new DefinedRuleAttributesValidator($this->parser, $this->attributeProvider);
    }

    /**
     * @dataProvider validateSuccessDataProvider
     * @param string $value
     * @param array $attributes
     */
    public function testValidateSuccess($value, array $attributes)
    {
        $this->attributeProvider->method('getAvailableRuleAttributes')->willReturnMap($attributes);

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
        $this->attributeProvider->method('getAvailableRuleAttributes')->willReturnMap($attributes);

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
            ['Product.msrp.value + 1', [['OroB2B\Bundle\ProductBundle\Entity\Product::msrp', ['value']]]],
        ];
    }


    /**
     * @return array
     */
    public function validateErrorDataProvider()
    {
        return [
            ['xxx', []],
            ['product.sku == SKU"', [['OroB2B\Bundle\ProductBundle\Entity\Product', ['sku', 'msrp']]]],
            ['Product.msrp.value + 1', [['OroB2B\Bundle\ProductBundle\Entity\Product::msrp', []]]],
        ];
    }
}
