<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Mapper;

use Oro\Bundle\CheckoutBundle\WorkflowState\Mapper\ShipToBillingDiffMapper;

class ShipToBillingDiffMapperTest extends AbstractCheckoutDiffMapperTest
{
    public function testGetName()
    {
        $this->assertEquals('shipToBillingAddress', $this->mapper->getName());
    }

    public function testGetCurrentState()
    {
        $this->checkout->setShipToBillingAddress(true);

        $this->assertTrue($this->mapper->getCurrentState($this->checkout));
    }

    public function testIsStatesEqualTrue()
    {
        $this->assertEquals(true, $this->mapper->isStatesEqual($this->checkout, true, true));
    }

    public function testIsStatesEqualFalse()
    {

        $this->assertFalse($this->mapper->isStatesEqual($this->checkout, true, false));
    }

    /**
     * {@inheritdoc}
     */
    protected function getMapper()
    {
        return new ShipToBillingDiffMapper();
    }
}
