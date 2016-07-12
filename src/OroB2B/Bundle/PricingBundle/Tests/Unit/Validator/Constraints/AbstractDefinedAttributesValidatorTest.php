<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraint;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\PricingBundle\Expression\ExpressionLanguageConverter;
use OroB2B\Bundle\PricingBundle\Expression\ExpressionParser;
use OroB2B\Bundle\PricingBundle\Provider\PriceRuleAttributeProvider;
use OroB2B\Bundle\PricingBundle\Validator\Constraints\AbstractDefinedAttributesValidator;

abstract class AbstractDefinedAttributesValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ExpressionParser
     */
    protected $parser;

    /**
     * @var PriceRuleAttributeProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $attributeProvider;

    /**
     * @var AbstractDefinedAttributesValidator
     */
    protected $definedAttributesValidator;

    protected function setUp()
    {
        $expressionConverter = new ExpressionLanguageConverter();
        $this->parser = new ExpressionParser($expressionConverter);
        $this->attributeProvider = $this->getMockBuilder(PriceRuleAttributeProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->parser->addNameMapping('Product', Product::class);
    }

    /**
     * @param string $value
     * @param ExecutionContextInterface $context
     */
    protected function doTestValidation($value, ExecutionContextInterface $context)
    {
        /** @var Constraint|\PHPUnit_Framework_MockObject_MockObject $constraint * */
        $constraint = $this->getMockBuilder(Constraint::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->definedAttributesValidator->initialize($context);
        $this->definedAttributesValidator->validate($value, $constraint);
    }
}
