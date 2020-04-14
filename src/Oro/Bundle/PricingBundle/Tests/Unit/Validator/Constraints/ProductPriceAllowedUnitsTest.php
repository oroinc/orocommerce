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
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class ProductPriceAllowedUnitsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ProductPriceAllowedUnits
     */
    protected $constraint;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ExecutionContextInterface
     */
    protected $context;

    /**
     * @var ProductPriceAllowedUnitsValidator
     */
    protected $validator;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->constraint = new ProductPriceAllowedUnits();
        $this->context = $this->createMock(ExecutionContextInterface::class);

        $this->validator = new ProductPriceAllowedUnitsValidator();
        $this->validator->initialize($this->context);
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown(): void
    {
        unset($this->constraint, $this->context, $this->validator);
    }

    public function testConfiguration()
    {
        $this->assertEquals('oro_pricing_product_price_allowed_units_validator', $this->constraint->validatedBy());
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
            ->method('buildViolation');

        $this->validator->validate($price, $this->constraint);
    }

    public function testValidateWithNotAllowedUnit()
    {
        $unit = new ProductUnit();
        $unit->setCode('invalidCode');

        $price = $this->getProductPrice();
        $price->setUnit($unit);

        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($this->constraint->notAllowedUnitMessage)
            ->willReturn($builder);
        $builder->expects($this->once())
            ->method('atPath')
            ->with('unit')
            ->willReturnSelf();
        $builder->expects($this->once())
            ->method('setParameters')
            ->with([
                '%product%' => $price->getProduct()->getSku(),
                '%unit%' => $unit->getCode()
            ])
            ->willReturnSelf();
        $builder->expects($this->once())
            ->method('addViolation');

        $this->validator->validate($price, $this->constraint);
    }

    public function testValidateNotExistingUnit()
    {
        $price = $this->getProductPrice();

        // Set null to product unit
        $class = new \ReflectionClass($price);
        $prop  = $class->getProperty('unit');
        $prop->setAccessible(true);
        $prop->setValue($price, null);

        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($this->constraint->notExistingUnitMessage)
            ->willReturn($builder);
        $builder->expects($this->once())
            ->method('atPath')
            ->with('unit')
            ->willReturnSelf();
        $builder->expects($this->once())
            ->method('addViolation');

        $this->validator->validate($price, $this->constraint);
    }

    public function testValidateNotExistingProduct()
    {
        $price = $this->getProductPrice();

        // Set null to product and productSku
        $class = new \ReflectionClass($price);
        $product  = $class->getProperty('product');
        $product->setAccessible(true);
        $product->setValue($price, null);

        $productSku  = $class->getProperty('productSku');
        $productSku->setAccessible(true);
        $productSku->setValue($price, null);

        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($this->constraint->notExistingProductMessage)
            ->willReturn($builder);
        $builder->expects($this->once())
            ->method('atPath')
            ->with('product')
            ->willReturnSelf();
        $builder->expects($this->once())
            ->method('addViolation');

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
