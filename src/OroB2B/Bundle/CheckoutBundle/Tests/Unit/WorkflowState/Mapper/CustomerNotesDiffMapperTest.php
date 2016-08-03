<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Mapper;

use OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper\CustomerNotesDiffMapper;

class CustomerNotesDiffMapperTest extends AbstractCheckoutDiffMapperTest
{
    protected function setUp()
    {
        parent::setUp();

        $this->mapper = new CustomerNotesDiffMapper();
    }

    public function testGetName()
    {
        $this->assertEquals('customerNotes', $this->mapper->getName());
    }

    public function testGetCurrentState()
    {
        $this->checkout
            ->expects($this->once())
            ->method('getCustomerNotes')
            ->willReturn('testCustomerNotes');

        $result = $this->mapper->getCurrentState($this->checkout);

        $this->assertEquals('testCustomerNotes', $result);
    }

    public function testIsStateActualTrue()
    {
        $this->checkout
            ->expects($this->once())
            ->method('getCustomerNotes')
            ->willReturn('testCustomerNotes');

        $savedState = [
            'parameter1' => 10,
            'customerNotes' => 'testCustomerNotes',
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual($this->checkout, $savedState);

        $this->assertEquals(true, $result);
    }

    public function testIsStateActualFalse()
    {
        $this->checkout
            ->expects($this->once())
            ->method('getCustomerNotes')
            ->willReturn('changedCustomerNotes');

        $savedState = [
            'parameter1' => 10,
            'customerNotes' => 'testCustomerNotes',
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual($this->checkout, $savedState);

        $this->assertEquals(false, $result);
    }

    public function testIsStateActualParameterDoesntExist()
    {
        $this->checkout
            ->expects($this->never())
            ->method('getCustomerNotes');

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
            ->method('getCustomerNotes');

        $savedState = [
            'parameter1' => 10,
            'customerNotes' => 1,
            'parameter3' => 'green',
        ];

        $result = $this->mapper->isStateActual($this->checkout, $savedState);

        $this->assertEquals(true, $result);
    }
}
