<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Mapper;

use OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper\PoNumberDiffMapper;

class PoNumberDiffMapperTest extends AbstractCheckoutDiffMapperTest
{
    public function setUp()
    {
        parent::setUp();

        $this->mapper = new PoNumberDiffMapper();
    }

    public function testGetName()
    {
        $this->assertEquals('poNumber', $this->mapper->getName());
    }

    public function testGetCurrentState()
    {
        $this->checkout
            ->expects($this->once())
            ->method('getPoNumber')
            ->willReturn('testPoNumber');

        $result = $this->mapper->getCurrentState($this->checkout);

        $this->assertEquals('testPoNumber', $result);
    }

    public function testIsStateActualTrue()
    {
        $this->checkout
            ->expects($this->once())
            ->method('getPoNumber')
            ->willReturn('testPoNumber');

        $savedState = [
            'parameter1' => 10,
            'poNumber' => 'testPoNumber',
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual($this->checkout, $savedState);

        $this->assertEquals(true, $result);
    }

    public function testIsStateActualFalse()
    {
        $this->checkout
            ->expects($this->once())
            ->method('getPoNumber')
            ->willReturn('changedPoNumber');

        $savedState = [
            'parameter1' => 10,
            'poNumber' => 'testPoNumber',
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual($this->checkout, $savedState);

        $this->assertEquals(false, $result);
    }

    public function testIsStateActualParameterDoesntExist()
    {
        $this->checkout
            ->expects($this->never())
            ->method('getPoNumber');

        $savedState = [
            'parameter1' => 10,
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual($this->checkout, $savedState);

        $this->assertEquals(true, $result);
    }

    public function testIsStateActualParameterOfWrongType()
    {
        $this->checkout
            ->expects($this->never())
            ->method('getPoNumber');

        $savedState = [
            'parameter1' => 10,
            'poNumber' => 1,
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual($this->checkout, $savedState);

        $this->assertEquals(true, $result);
    }
}
