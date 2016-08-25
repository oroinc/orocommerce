<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Mapper;

use Oro\Bundle\CheckoutBundle\WorkflowState\Mapper\ShipUntilDiffMapper;

class ShipUntilDiffMapperTest extends AbstractCheckoutDiffMapperTest
{
    public function testGetName()
    {
        $this->assertEquals('shipUntil', $this->mapper->getName());
    }

    public function testGetCurrentState()
    {
        $now = new \DateTime();

        $this->checkout->setShipUntil($now);

        $result = $this->mapper->getCurrentState($this->checkout);

        $this->assertEquals($now, $result);
    }

    public function testIsStatesEqualTrue()
    {
        $this->assertTrue($this->mapper->isStatesEqual(
            $this->checkout,
            new \DateTime('2016-01-01'),
            new \DateTime('2016-01-01')
        ));
    }

    public function testIsStatesEqualFalse()
    {
        $this->assertFalse($this->mapper->isStatesEqual(
            $this->checkout,
            new \DateTime('2016-02-01'),
            new \DateTime('2016-01-01')
        ));
    }

    /**
     * {@inheritdoc}
     */
    protected function getMapper()
    {
        return new ShipUntilDiffMapper();
    }
}
