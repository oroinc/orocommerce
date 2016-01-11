<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Factory;

use OroB2B\Bundle\TaxBundle\Factory\TaxFactory;
use OroB2B\Bundle\TaxBundle\Mapper\TaxMapperInterface;
use OroB2B\Bundle\TaxBundle\Model\Taxable;

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
        $mapper = $this->getMock('OroB2B\Bundle\TaxBundle\Mapper\TaxMapperInterface');
        $mapper
            ->expects($this->once())
            ->method('getProcessingClassName')
            ->willReturn('stdClass');

        $mapper
            ->expects($this->once())
            ->method('map')
            ->willReturn(new Taxable());

        $this->factory->addMapper($mapper);
        $taxable = $this->factory->create(new \stdClass());
        $this->assertInstanceOf('\OroB2B\Bundle\TaxBundle\Model\Taxable', $taxable);
    }

    /**
     * @expectedException \OroB2B\Bundle\TaxBundle\Mapper\UnmappableArgumentException
     * @expectedExceptionMessage Can't find Tax Mapper for object "stdClass"
     */
    public function testCreateThrowExceptionWithoutMapper()
    {
        $this->factory->create(new \stdClass());
    }
}
