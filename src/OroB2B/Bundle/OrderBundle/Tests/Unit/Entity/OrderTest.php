<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Entity;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTestCase;

use OroB2B\Bundle\OrderBundle\Entity\Order;

class OrderTest extends EntityTestCase
{
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
