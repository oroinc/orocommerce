<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Factory;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\TaxBundle\Factory\TaxFactory;
use Oro\Bundle\TaxBundle\Mapper\TaxMapperInterface;
use Oro\Bundle\TaxBundle\Model\Taxable;

class TaxFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TaxFactory
     */
    protected $factory;

    protected function setUp()
    {
        $this->factory = new TaxFactory();
    }

    protected function tearDown()
    {
        unset($this->factory);
    }

    public function testAddMapperAndCreate()
    {
        /** @var TaxMapperInterface|\PHPUnit_Framework_MockObject_MockObject $mapper */
        $mapper = $this->getMock('Oro\Bundle\TaxBundle\Mapper\TaxMapperInterface');
        $mapper
            ->expects($this->once())
            ->method('getProcessingClassName')
            ->willReturn('Oro\Bundle\OrderBundle\Entity\Order');

        $mapper
            ->expects($this->exactly(2))
            ->method('map')
            ->willReturnCallback(
                function () {
                    return new Taxable();
                }
            );

        $this->factory->addMapper($mapper);
        $object = new Order();

        $object->setSubtotal(45.5);
        $taxable = $this->factory->create($object);
        $this->assertInstanceOf('\Oro\Bundle\TaxBundle\Model\Taxable', $taxable);

        $object->setSubtotal(50);
        $anotherTaxable = $this->factory->create($object);

        $this->assertInstanceOf('\Oro\Bundle\TaxBundle\Model\Taxable', $anotherTaxable);
        $this->assertNotSame($taxable, $anotherTaxable);
    }

    /**
     * @expectedException \Oro\Bundle\TaxBundle\Mapper\UnmappableArgumentException
     * @expectedExceptionMessage Can't find Tax Mapper for object "stdClass"
     */
    public function testCreateThrowExceptionWithoutMapper()
    {
        $this->factory->create(new \stdClass());
    }

    public function testSupports()
    {
        $this->assertFalse($this->factory->supports(new \stdClass()));

        /** @var TaxMapperInterface|\PHPUnit_Framework_MockObject_MockObject $mapper */
        $mapper = $this->getMock('Oro\Bundle\TaxBundle\Mapper\TaxMapperInterface');
        $mapper
            ->expects($this->once())
            ->method('getProcessingClassName')
            ->willReturn('stdClass');

        $this->factory->addMapper($mapper);
        $this->assertTrue($this->factory->supports(new \stdClass()));

    }
}
