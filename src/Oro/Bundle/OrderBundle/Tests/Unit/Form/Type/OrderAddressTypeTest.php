<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AddressBundle\Form\Type\AddressType;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Form\Type\OrderAddressType;
use Oro\Component\Testing\Unit\PreloadedExtension;

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
        $this->formType->setDataClass('Oro\Bundle\OrderBundle\Entity\OrderAddress');
    }

    public function testGetName()
    {
        $this->assertEquals(OrderAddressType::NAME, $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals(AddressType::class, $this->formType->getParent());
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return array_merge(
            parent::getExtensions(),
            [new PreloadedExtension([$this->formType], [])]
        );
    }

    /**
     * @return Order
     */
    protected function getEntity()
    {
        return new Order();
    }
}
