<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Form\Type;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Form\Type\OrderAddressType;

class OrderAddressTypeTest extends AbstractOrderAddressTypeTest
{
    protected function initFormType()
    {
        $this->formType = new OrderAddressType(
            $this->addressFormatter,
            $this->orderAddressManager,
            $this->orderAddressSecurityProvider,
            $this->serializer
        );
        $this->formType->setDataClass('OroB2B\Bundle\OrderBundle\Entity\OrderAddress');
    }

    public function testGetName()
    {
        $this->assertEquals(OrderAddressType::NAME, $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('oro_address', $this->formType->getParent());
    }

    /**
     * @return Order
     */
    protected function getEntity()
    {
        return new Order();
    }
}
