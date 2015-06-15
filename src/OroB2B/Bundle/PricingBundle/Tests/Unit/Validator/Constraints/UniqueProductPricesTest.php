<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Validator\Constraint;

use Oro\Bundle\CurrencyBundle\Model\Price;

use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\PricingBundle\Validator\Constraints\UniqueProductPrices;
use OroB2B\Bundle\PricingBundle\Validator\Constraints\UniqueProductPricesValidator;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

class UniqueProductPricesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UniqueProductPrices
     */
    protected $constraint;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\Validator\ExecutionContextInterface
     */
    protected $context;

    /**
     * @var UniqueProductPricesValidator
     */
    protected $validator;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->constraint = new UniqueProductPrices();
        $this->context = $this->getMock('Symfony\Component\Validator\ExecutionContextInterface');

        $this->validator = new UniqueProductPricesValidator();
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
        $this->assertEquals('orob2b_pricing_unique_product_prices_validator', $this->constraint->validatedBy());
        $this->assertEquals(Constraint::PROPERTY_CONSTRAINT, $this->constraint->getTargets());
    }

    public function testGetDefaultOption()
    {
        $this->assertNull($this->constraint->getDefaultOption());
    }

    public function testValidateWithoutDuplications()
    {
        $this->context->expects($this->never())
            ->method('addViolation');

        $data = new ArrayCollection([
            $this->createPriceList(1, 10, 'kg', 'USD'),
            $this->createPriceList(2, 10, 'kg', 'USD'),
            $this->createPriceList(1, 100, 'kg', 'USD'),
            $this->createPriceList(1, 10, 'item', 'USD'),
            $this->createPriceList(1, 10, 'kg', 'EUR')
        ]);

        $this->validator->validate($data, $this->constraint);
    }

    public function testValidateWithDuplications()
    {
        $this->context->expects($this->once())
            ->method('addViolation')
            ->with($this->constraint->message);

        $data = new ArrayCollection([
            $this->createPriceList(1, 10, 'kg', 'USD'),
            $this->createPriceList(1, 10, 'kg', 'USD')
        ]);

        $this->validator->validate($data, $this->constraint);
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "array or Traversable and ArrayAccess", "string" given
     */
    public function testUnexpectedValue()
    {
        $data = 'string';
        $this->validator->validate($data, $this->constraint);
    }

    public function testUnexpectedItem()
    {
        $this->setExpectedException(
            '\Symfony\Component\Validator\Exception\UnexpectedTypeException',
            'Expected argument of type "OroB2B\Bundle\PricingBundle\Entity\ProductPrice", "stdClass" given'
        );
        $data = new ArrayCollection([ new \stdClass()]);
        $this->validator->validate($data, $this->constraint);
    }

    /**
     * @param integer $priceListId
     * @param integer $quantity
     * @param string $unitCode
     * @param string $currency
     * @return ProductPrice
     */
    protected function createPriceList($priceListId, $quantity, $unitCode, $currency)
    {
        $unit = new ProductUnit();
        $unit->setCode($unitCode);

        $price = new Price();
        $price
            ->setValue(100)
            ->setCurrency($currency);

        $productPrice = new ProductPrice();
        $productPrice
            ->setProduct($this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', 42))
            ->setPriceList($this->getEntity('OroB2B\Bundle\PricingBundle\Entity\PriceList', $priceListId))
            ->setQuantity($quantity)
            ->setUnit($unit)
            ->setPrice($price);

        return $productPrice;
    }

    /**
     * @param string $className
     * @param int $id
     * @return object
     */
    protected function getEntity($className, $id)
    {
        $entity = new $className;
        $reflectionClass = new \ReflectionClass($className);
        $method = $reflectionClass->getProperty('id');
        $method->setAccessible(true);
        $method->setValue($entity, $id);
        return $entity;
    }
}
