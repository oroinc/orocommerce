<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraint;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\PricingBundle\Expression\ExpressionLanguageConverter;
use OroB2B\Bundle\PricingBundle\Expression\ExpressionParser;
use OroB2B\Bundle\PricingBundle\Provider\PriceRuleFieldsProvider;
use OroB2B\Bundle\PricingBundle\Validator\Constraints\AbstractPriceRuleExpressionValidator;

abstract class AbstractDefinedAttributesValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ExpressionParser
     */
    protected $parser;

    /**
     * @var PriceRuleFieldsProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $fieldsProvider;

    /**
     * @var AbstractPriceRuleExpressionValidator
     */
    protected $expressionValidator;

    protected function setUp()
    {
        $expressionConverter = new ExpressionLanguageConverter();
        $this->parser = new ExpressionParser($expressionConverter);
        $this->fieldsProvider = $this->getMockBuilder(PriceRuleFieldsProvider::class)
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
        $this->expressionValidator->initialize($context);
        $this->expressionValidator->validate($value, $constraint);
    }
}
