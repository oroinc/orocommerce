<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

use Oro\Bundle\CurrencyBundle\Model\Price;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\PricingBundle\Validator\Constraints\ProductPriceCurrency;
use OroB2B\Bundle\PricingBundle\Validator\Constraints\ProductPriceCurrencyValidator;

class ProductPriceCurrencyTest extends \PHPUnit_Framework_TestCase
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
        $this->context = $this->getMock('Symfony\Component\Validator\ExecutionContextInterface');

        $this->validator = new ProductPriceCurrencyValidator();
        $this->validator->initialize($this->context);
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        unset($this->constraint, $this->context, $this->validator);
    }

    public function testConfiguration()
    {
        $this->assertEquals('orob2b_pricing_product_price_currency_validator', $this->constraint->validatedBy());
        $this->assertEquals(Constraint::CLASS_CONSTRAINT, $this->constraint->getTargets());
    }

    public function testGetDefaultOption()
    {
        $this->assertNull($this->constraint->getDefaultOption());
    }

    public function testValidateWithAllowedPrice()
    {
        $price = new Price();
        $price
            ->setValue('50')
            ->setCurrency('USD');

        $productPrice = $this->getProductPrice();
        $productPrice->setPrice($price);

        $this->context->expects($this->never())
            ->method('addViolationAt');

        $this->validator->validate($productPrice, $this->constraint);
    }

    public function testValidateWithNotAllowedCurrency()
    {
        $invalidCurrency = 'ABC';

        $price = new Price();
        $price
            ->setValue('50')
            ->setCurrency($invalidCurrency);

        $productPrice = $this->getProductPrice();
        $productPrice->setPrice($price);

        $this->context->expects($this->once())
            ->method('addViolationAt')
            ->with(
                'price.currency',
                $this->constraint->message,
                $this->equalTo(['%invalidCurrency%' => $invalidCurrency])
            );

        $this->validator->validate($productPrice, $this->constraint);
    }

    public function testNotExpectedValueException()
    {
        $this->setExpectedException(
            '\InvalidArgumentException',
            'Value must be instance of "OroB2B\Bundle\PricingBundle\Entity\ProductPrice", "stdClass" given'
        );
        $this->validator->validate(new \stdClass(), $this->constraint);
    }

    public function testWithoutPrice()
    {
        $productPrice = new ProductPrice();

        $this->context->expects($this->never())
            ->method('addViolationAt');

        $this->validator->validate($productPrice, $this->constraint);
    }

    /**
     * @return ProductPrice
     */
    public function getProductPrice()
    {
        $priceList = new PriceList();
        $priceList->setCurrencies(['USD', 'EUR']);

        $productPrice = new ProductPrice();
        $productPrice->setPriceList($priceList);

        return $productPrice;
    }
}
