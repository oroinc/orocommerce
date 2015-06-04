<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

use Oro\Bundle\CurrencyBundle\Model\Price;

use OroB2B\Bundle\PricingBundle\Validator\Constraints\PriceProductAllowedUnitsValidator;
use OroB2B\Bundle\PricingBundle\Validator\Constraints\PriceProductAllowedUnits;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;

class PriceProductAllowedUnitsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PriceProductAllowedUnits
     */
    protected $constraint;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\Validator\ExecutionContextInterface
     */
    protected $context;

    /**
     * @var PriceProductAllowedUnitsValidator
     */
    protected $validator;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->constraint = new PriceProductAllowedUnits();
        $this->context = $this->getMock('Symfony\Component\Validator\ExecutionContextInterface');

        $this->validator = new PriceProductAllowedUnitsValidator();
        $this->validator->initialize($this->context);
    }

    public function testConfiguration()
    {
        $this->assertEquals('orob2b_pricing_price_product_allowed_units_validator', $this->constraint->validatedBy());
        $this->assertEquals(Constraint::CLASS_CONSTRAINT, $this->constraint->getTargets());
    }

    public function testGetDefaultOption()
    {
        $this->assertNull($this->constraint->getDefaultOption());
    }

    /**
     * @dataProvider testValidatePriceDataProvider
     * @param ProductPrice $price
     * @param $valid
     */
    public function testValidatePrice(ProductPrice $price, $valid)
    {
        if ($valid) {
            $this->context->expects($this->never())
                ->method('addViolation');
        } else {
            $this->context->expects($this->once())
                ->method('addViolation')
                ->with($this->constraint->message);

        }

        $this->validator->validate($price, $this->constraint);
    }

    /**
     * @return array
     */
    public function testValidatePriceDataProvider()
    {
        $priceList = new PriceList();

        $unit = new ProductUnit();
        $unit->setCode('kg');

        $unitPrecision = new ProductUnitPrecision();
        $unitPrecision->setUnit($unit)
            ->setPrecision(3);

        $product = new Product();
        $product->setSku('testSku')->addUnitPrecision($unitPrecision);

        $productPrice = new ProductPrice();
        $productPrice->setPriceList($priceList)
            ->setProduct($product)
            ->setQuantity('10')
            ->setPrice((new Price())->setValue('50')->setCurrency('USD'));

        $validPrice = clone($productPrice);
        $validPrice->setUnit((new ProductUnit())->setCode('kg'));

        $invalidPrice = clone($productPrice);
        $invalidPrice->setUnit((new ProductUnit())->setCode('invalidUnitCode'));

        return [
            'valid' => [
                'price' => $validPrice,
                'valid' => true
            ],
            'invalid' => [
                'price' => $invalidPrice,
                'valid' => false
            ]
        ];
    }
}
