<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Mapper;

use Oro\Bundle\CheckoutBundle\WorkflowState\Mapper\PoNumberDiffMapper;

class PoNumberDiffMapperTest extends AbstractCheckoutDiffMapperTest
{
    public function testGetName()
    {
        $this->assertEquals('poNumber', $this->mapper->getName());
    }

    public function testGetCurrentState()
    {
        $this->checkout->setPoNumber('testPoNumber');

        $result = $this->mapper->getCurrentState($this->checkout);
        $this->assertEquals('testPoNumber', $result);
    }

    public function testIsStatesEqualTrue()
    {
        $this->assertTrue($this->mapper->isStatesEqual($this->checkout, '111111111', '111111111'));
    }

    public function testIsStatesEqualFalse()
    {
        $this->assertFalse($this->mapper->isStatesEqual($this->checkout, '111111111', '22222222'));
    }

    /**
     * {@inheritdoc}
     */
    protected function getMapper()
    {
        return new PoNumberDiffMapper();
    }
}
