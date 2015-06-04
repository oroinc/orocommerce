<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

use Oro\Bundle\CurrencyBundle\Model\Price;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\PricingBundle\Validator\Constraints\ProductPriceAllowedUnits;
use OroB2B\Bundle\PricingBundle\Validator\Constraints\ProductPriceAllowedUnitsValidator;

class ProductPriceAllowedUnitsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductPriceAllowedUnits
     */
    protected $constraint;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\Validator\ExecutionContextInterface
     */
    protected $context;

    /**
     * @var ProductPriceAllowedUnitsValidator
     */
    protected $validator;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->constraint = new ProductPriceAllowedUnits();
        $this->context = $this->getMock('Symfony\Component\Validator\ExecutionContextInterface');

        $this->validator = new ProductPriceAllowedUnitsValidator();
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
        $this->assertEquals('orob2b_pricing_product_price_allowed_units_validator', $this->constraint->validatedBy());
        $this->assertEquals(Constraint::CLASS_CONSTRAINT, $this->constraint->getTargets());
    }

    public function testGetDefaultOption()
    {
        $this->assertNull($this->constraint->getDefaultOption());
    }

    public function testValidateWithAllowedUnit()
    {
        $unit = new ProductUnit();
        $unit->setCode('kg');

        $price = $this->getProductPrice();
        $price->setUnit($unit);

        $this->context->expects($this->never())
            ->method('addViolation');

        $this->validator->validate($price, $this->constraint);
    }

    public function testValidateWithNotAllowedUnit()
    {
        $unit = new ProductUnit();
        $unit->setCode('invalidCode');

        $price = $this->getProductPrice();
        $price->setUnit($unit);

        $this->context->expects($this->once())
            ->method('addViolation')
            ->with($this->constraint->message);

        $this->validator->validate($price, $this->constraint);
    }

    /**
     * @return ProductPrice
     */
    public function getProductPrice()
    {
        $unit = new ProductUnit();
        $unit->setCode('kg');

        $unitPrecision = new ProductUnitPrecision();
        $unitPrecision
            ->setUnit($unit)
            ->setPrecision(3);

        $product = new Product();
        $product
            ->setSku('testSku')
            ->addUnitPrecision($unitPrecision);

        $price = new Price();
        $price
            ->setValue('50')
            ->setCurrency('USD');

        $productPrice = new ProductPrice();
        $productPrice
            ->setPriceList(new PriceList())
            ->setProduct($product)
            ->setQuantity('10')
            ->setPrice($price);

        return $productPrice;
    }
}
