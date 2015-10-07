<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;

use OroB2B\Bundle\OrderBundle\Doctrine\ORM\Id\EntityAwareGeneratorInterface;
use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\EventListener\OrderListener;

class OrderListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|EntityAwareGeneratorInterface */
    protected $generator;

    /** @var OrderListener */
    protected $listener;

    protected function setUp()
    {
        $this->generator = $this->getMock('OroB2B\Bundle\OrderBundle\Doctrine\ORM\Id\EntityAwareGeneratorInterface');

        $this->listener = new OrderListener($this->generator);
    }

    protected function tearDown()
    {
        unset($this->generator, $this->listener);
    }

    public function testPostPersist()
    {
        $this->generator->expects($this->once())
            ->method('generate')
            ->willReturn(125);

        /** @var Order|\PHPUnit_Framework_MockObject_MockObject $orderMock */
        $orderMock = $this->getMock('OroB2B\Bundle\OrderBundle\Entity\Order');
        $orderMock->expects($this->once())->method('setIdentifier')->with(125);

        $this->listener->postPersist($this->getLifecycleEventArgs($orderMock));
    }

    public function testPostPersistOrderWithIdentifier()
    {
        $this->generator->expects($this->never())
            ->method('generate');

        /** @var Order|\PHPUnit_Framework_MockObject_MockObject $orderMock */
        $orderMock = $this->getMock('OroB2B\Bundle\OrderBundle\Entity\Order');
        $orderMock->expects($this->once())->method('getIdentifier')->willReturn(125);

        $this->listener->postPersist($this->getLifecycleEventArgs($orderMock));
    }

    /**
     * @param Order $order
     * @return LifecycleEventArgs|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getLifecycleEventArgs(Order $order)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|LifecycleEventArgs $lifecycleEventArgs */
        $lifecycleEventArgs = $this->getMockBuilder('Doctrine\ORM\Event\LifecycleEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $lifecycleEventArgs->expects($this->once())
            ->method('getEntity')
            ->willReturn($order);

        return $lifecycleEventArgs;
    }
}
