<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Validator\Constraints\ProductPriceCurrency;
use Oro\Bundle\PricingBundle\Validator\Constraints\ProductPriceCurrencyValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ProductPriceCurrencyValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductPriceCurrency
     */
    protected $constraint;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\Validator\ExecutionContextInterface
     */
    protected $context;

    /**
     * @var ProductPriceCurrencyValidator
     */
    protected $validator;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->constraint = new ProductPriceCurrency();
        $this->context = $this->createMock(ExecutionContextInterface::class);

        $this->validator = new ProductPriceCurrencyValidator();
        $this->validator->initialize($this->context);
    }

    public function testConfiguration()
    {
        $this->assertEquals('oro_pricing_product_price_currency_validator', $this->constraint->validatedBy());
        $this->assertEquals(Constraint::CLASS_CONSTRAINT, $this->constraint->getTargets());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage must be instance of "Oro\Bundle\PricingBundle\Entity\ProductPrice", "stdClass" given
     */
    public function testNotExpectedValueException()
    {
        $this->validator->validate(new \stdClass(), $this->constraint);
    }

    public function testValidateWithoutPriceList()
    {
        $productPrice = new ProductPrice();
        $productPrice->setPrice(new Price());
        $this->context->expects(static::never())->method('addViolationAt');
        $this->validator->validate($productPrice, $this->constraint);
    }

    public function testValidPrice()
    {
        $productPrice = new ProductPrice();
        $productPrice->getPriceList(new PriceList());
        $productPrice->setPrice(new Price());
        $this->context->expects(static::never())->method('addViolationAt');
        $this->validator->validate($productPrice, $this->constraint);
    }

    public function testInvalidPrice()
    {
        $productPrice = new ProductPrice();
        $priceList = new PriceList();
        $productPrice->setPriceList($priceList);
        $productPrice->setPrice(Price::create(1, 'USD'));
        $this->context->expects(static::once())->method('addViolationAt');
        $this->validator->validate($productPrice, $this->constraint);
    }
}
