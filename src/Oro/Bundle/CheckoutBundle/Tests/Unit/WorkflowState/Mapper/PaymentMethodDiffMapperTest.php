<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Mapper;

use Oro\Bundle\CheckoutBundle\WorkflowState\Mapper\PaymentMethodDiffMapper;

class PaymentMethodDiffMapperTest extends AbstractCheckoutDiffMapperTest
{
    public function testGetName()
    {
        $this->assertEquals('paymentMethod', $this->mapper->getName());
    }

    public function testGetCurrentState()
    {
        $this->checkout->setPaymentMethod('payflow_gateway');

        $this->assertEquals('payflow_gateway', $this->mapper->getCurrentState($this->checkout));
    }

    public function testIsStatesEqualTrue()
    {
        $this->assertEquals(true, $this->mapper->isStatesEqual($this->checkout, 'payflow_gateway', 'payflow_gateway'));
    }

    public function testIsStatesEqualFalse()
    {
        $this->assertEquals(false, $this->mapper->isStatesEqual($this->checkout, 'payflow_gateway', 'payment_term'));
    }

    /**
     * {@inheritdoc}
     */
    protected function getMapper()
    {
        return new PaymentMethodDiffMapper();
    }
}
