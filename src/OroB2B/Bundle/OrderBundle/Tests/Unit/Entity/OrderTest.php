<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;

class OrderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $now = new \DateTime('now');
        $properties = [
            ['id', '123'],
            ['identifier', 'identifier-test-01'],
            ['owner', new User()],
            ['organization', new Organization()],
            ['createdAt', $now, false],
            ['updatedAt', $now, false],
        ];

        $this->assertPropertyAccessors(new Order(), $properties);
    }

    public function testCollections()
    {
        $this->assertPropertyCollection(new Order(), 'addresses', new OrderAddress());
    }

    public function testTypedAddresses()
    {
        $shippingAddressType = new AddressType(AddressType::TYPE_SHIPPING);
        $billingAddressType = new AddressType(AddressType::TYPE_BILLING);

        $shippingAddress = new OrderAddress();
        $shippingAddress->setTypes(new ArrayCollection([$shippingAddressType]));

        $billingAddress = new OrderAddress();
        $billingAddress->setTypes(new ArrayCollection([$billingAddressType]));

        $order = new Order();
        $this->assertEmpty($order->getAddresses());

        $order->resetAddresses([$shippingAddress, $billingAddress]);
        $this->assertTrue($order->hasAddress($billingAddress));
        $this->assertTrue($order->hasAddress($shippingAddress));

        $this->assertSame($shippingAddress, $order->getShippingAddress());
        $this->assertSame($billingAddress, $order->getBillingAddress());
    }

    public function testPrePersist()
    {
        $order = new Order();
        $order->prePersist();
        $this->assertInstanceOf('\DateTime', $order->getCreatedAt());
        $this->assertInstanceOf('\DateTime', $order->getUpdatedAt());
    }

    public function testPreUpdate()
    {
        $order = new Order();
        $order->preUpdate();
        $this->assertInstanceOf('\DateTime', $order->getUpdatedAt());
    }
}
