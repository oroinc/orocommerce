<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Validator\Constraint;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

use OroB2B\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use OroB2B\Bundle\ShippingBundle\Validator\Constraints\UniqueProductUnitShippingOptions;
use OroB2B\Bundle\ShippingBundle\Validator\Constraints\UniqueProductUnitShippingOptionsValidator;

class UniqueProductUnitShippingOptionsTest extends \PHPUnit_Framework_TestCase
{
    /** @var UniqueProductUnitShippingOptions */
    protected $constraint;

    /** @var \PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\Validator\ExecutionContextInterface */
    protected $context;

    /** @var UniqueProductUnitShippingOptionsValidator */
    protected $validator;

    protected function setUp()
    {
        $this->constraint = new UniqueProductUnitShippingOptions();
        $this->context = $this->getMock('Symfony\Component\Validator\ExecutionContextInterface');

        $this->validator = new UniqueProductUnitShippingOptionsValidator();
        $this->validator->initialize($this->context);
    }

    protected function tearDown()
    {
        unset($this->constraint, $this->context, $this->validator);
    }

    public function testConfiguration()
    {
        $this->assertEquals(
            'orob2b_shipping_unique_product_unit_shipping_options_validator',
            $this->constraint->validatedBy()
        );
        $this->assertEquals(Constraint::PROPERTY_CONSTRAINT, $this->constraint->getTargets());
    }

    public function testGetDefaultOption()
    {
        $this->assertNull($this->constraint->getDefaultOption());
    }

    public function testValidateWithoutDuplications()
    {
        $this->context->expects($this->never())->method('addViolation');

        $data = new ArrayCollection([
            $this->createProductShippingOptions('lbs'),
            $this->createProductShippingOptions('kg')
        ]);

        $this->validator->validate($data, $this->constraint);
    }

    public function testValidateWithDuplications()
    {
        $this->context->expects($this->once())->method('addViolation')->with($this->constraint->message);

        $data = new ArrayCollection([
            $this->createProductShippingOptions('kg'),
            $this->createProductShippingOptions('kg')
        ]);

        $this->validator->validate($data, $this->constraint);
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "array or Traversable and ArrayAccess", "string" given
     */
    public function testUnexpectedValue()
    {
        $this->validator->validate('test', $this->constraint);
    }

    public function testUnexpectedItem()
    {
        $this->setExpectedException(
            '\Symfony\Component\Validator\Exception\UnexpectedTypeException',
            'Expected argument of type "OroB2B\Bundle\ProductBundle\Model\ProductUnitHolderInterface", "stdClass" given'
        );
        $data = new ArrayCollection([ new \stdClass()]);
        $this->validator->validate($data, $this->constraint);
    }

    /**
     * @param string $code
     * @return ProductShippingOptions|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createProductShippingOptions($code)
    {
        /** @var ProductUnit|\PHPUnit_Framework_MockObject_MockObject $unit */
        $unit = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Entity\ProductUnit')
            ->disableOriginalConstructor()
            ->getMock();
        $unit->expects($this->atLeastOnce())->method('getCode')->willReturn($code);

        /** @var ProductShippingOptions|\PHPUnit_Framework_MockObject_MockObject $option */
        $option = $this->getMockBuilder('OroB2B\Bundle\ShippingBundle\Entity\ProductShippingOptions')
            ->disableOriginalConstructor()
            ->getMock();
        $option->expects($this->atLeastOnce())->method('getProductUnit')->willReturn($unit);


        return $option;
    }
}
