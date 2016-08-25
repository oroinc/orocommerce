<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Mapper;

use Oro\Bundle\CheckoutBundle\WorkflowState\Mapper\CustomerNotesDiffMapper;

class CustomerNotesDiffMapperTest extends AbstractCheckoutDiffMapperTest
{
    public function testGetName()
    {
        $this->assertEquals('customerNotes', $this->mapper->getName());
    }

    public function testGetCurrentState()
    {
        $this->checkout->setCustomerNotes('testCustomerNotes');

        $result = $this->mapper->getCurrentState($this->checkout);
        $this->assertEquals('testCustomerNotes', $result);
    }

    public function testIsStatesEqualTrue()
    {
        $this->assertTrue($this->mapper->isStatesEqual($this->checkout, 'testCustomerNotes', 'testCustomerNotes'));
    }

    public function testIsStatesEqualFalse()
    {
        $this->assertFalse($this->mapper->isStatesEqual($this->checkout, 'testCustomerNotes', 'anotherCustomerNotes'));
    }

    /**
     * {@inheritdoc}
     */
    protected function getMapper()
    {
        return new CustomerNotesDiffMapper();
    }
}
