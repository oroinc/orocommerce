<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Validator\Constraints\ProductPriceAllowedUnits;
use Oro\Bundle\PricingBundle\Validator\Constraints\ProductPriceAllowedUnitsValidator;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ProductPriceAllowedUnitsValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): ProductPriceAllowedUnitsValidator
    {
        return new ProductPriceAllowedUnitsValidator();
    }

    public function testGetTargets()
    {
        $constraint = new ProductPriceAllowedUnits();
        $this->assertEquals(Constraint::CLASS_CONSTRAINT, $constraint->getTargets());
    }

    public function testValidateWithAllowedUnit()
    {
        $unit = new ProductUnit();
        $unit->setCode('kg');

        $price = $this->getProductPrice();
        $price->setUnit($unit);

        $constraint = new ProductPriceAllowedUnits();
        $this->validator->validate($price, $constraint);
        $this->assertNoViolation();
    }

    public function testValidateWithNotAllowedUnit()
    {
        $unit = new ProductUnit();
        $unit->setCode('invalidCode');

        $price = $this->getProductPrice();
        $price->setUnit($unit);

        $constraint = new ProductPriceAllowedUnits();
        $this->validator->validate($price, $constraint);

        $this->buildViolation($constraint->notAllowedUnitMessage)
            ->setParameters([
                '%product%' => $price->getProduct()->getSku(),
                '%unit%' => $unit->getCode()
            ])
            ->atPath('property.path.unit')
            ->assertRaised();
    }

    public function testValidateNotExistingUnit()
    {
        $price = $this->getProductPrice();
        ReflectionUtil::setPropertyValue($price, 'unit', null);

        $constraint = new ProductPriceAllowedUnits();
        $this->validator->validate($price, $constraint);

        $this->buildViolation($constraint->notExistingUnitMessage)
            ->atPath('property.path.unit')
            ->assertRaised();
    }

    public function testValidateNotExistingProduct()
    {
        $price = $this->getProductPrice();
        ReflectionUtil::setPropertyValue($price, 'product', null);
        ReflectionUtil::setPropertyValue($price, 'productSku', null);

        $constraint = new ProductPriceAllowedUnits();
        $this->validator->validate($price, $constraint);

        $this->buildViolation($constraint->notExistingProductMessage)
            ->atPath('property.path.product')
            ->assertRaised();
    }

    private function getProductPrice(): ProductPrice
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
