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

    public function testIsStatesEqualTrue()
    {
        $state1 = [
            'parameter1' => 10,
            'customerNotes' => 'testCustomerNotes',
            'parameter3' => 'green',
        ];

        $state2 = [
            'parameter1' => 10,
            'customerNotes' => 'testCustomerNotes',
            'parameter3' => 'green',
        ];

        $entity = new \stdClass();

        $result = $this->mapper->isStatesEqual($entity, $state1, $state2);

        $this->assertEquals(true, $result);
    }

    public function testIsStatesEqualFalse()
    {
        $state1 = [
            'parameter1' => 10,
            'customerNotes' => 'testCustomerNotes',
            'parameter3' => 'green',
        ];

        $state2 = [
            'parameter1' => 10,
            'customerNotes' => 'incorrectCustomerNotes',
            'parameter3' => 'green',
        ];

        $entity = new \stdClass();

        $result = $this->mapper->isStatesEqual($entity, $state1, $state2);

        $this->assertEquals(false, $result);
    }

    public function testIsStatesEqualParameterNotExistInState1()
    {
        $state1 = [
            'parameter1' => 10,
            'parameter3' => 'green',
        ];

        $state2 = [
            'parameter1' => 10,
            'parameter3' => 'green',
            'customerNotes' => 'CustomerNotes'
        ];
        $entity = new \stdClass();

        $result = $this->mapper->isStatesEqual($entity, $state1, $state2);

        $this->assertEquals(true, $result);
    }

    public function testIsStatesEqualParameterNotExistInState2()
    {
        $state1 = [
            'parameter1' => 10,
            'parameter3' => 'green',
            'customerNotes' => 'CustomerNotes'
        ];

        $state2 = [
            'parameter1' => 10,
            'parameter3' => 'green',
        ];

        $entity = new \stdClass();

        $result = $this->mapper->isStatesEqual($entity, $state1, $state2);

        $this->assertEquals(true, $result);
    }
}
