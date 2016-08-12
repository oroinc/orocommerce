<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Mapper;

use OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper\PoNumberDiffMapper;

class PoNumberDiffMapperTest extends AbstractCheckoutDiffMapperTest
{
    protected function setUp()
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

    public function testIsStatesEqualTrue()
    {
        $state1 = [
            'parameter1' => 10,
            'poNumber' => '100000001',
            'parameter3' => 'green',
        ];

        $state2 = [
            'parameter1' => 10,
            'poNumber' => '100000001',
            'parameter3' => 'green',
        ];

        $this->assertEquals(true, $this->mapper->isStatesEqual($state1, $state2));
    }

    public function testIsStatesEqualFalse()
    {
        $state1 = [
            'parameter1' => 10,
            'poNumber' => '100000001',
            'parameter3' => 'green',
        ];

        $state2 = [
            'parameter1' => 10,
            'poNumber' => '100000002',
            'parameter3' => 'green',
        ];

        $this->assertEquals(false, $this->mapper->isStatesEqual($state1, $state2));
    }

    public function testIsStatesEqualParameterNotExistInState1()
    {
        $state1 = [
            'parameter1' => 10,
            'parameter3' => 'green',
        ];

        $state2 = [
            'parameter1' => 10,
            'poNumber' => '100000001',
            'parameter3' => 'green',
        ];

        $this->assertEquals(true, $this->mapper->isStatesEqual($state1, $state2));
    }

    public function testIsStatesEqualParameterNotExistInState2()
    {
        $state1 = [
            'parameter1' => 10,
            'parameter3' => 'green',
            'poNumber' => '100000001',
        ];

        $state2 = [
            'parameter1' => 10,
            'parameter3' => 'green',
        ];

        $this->assertEquals(true, $this->mapper->isStatesEqual($state1, $state2));
    }
}
