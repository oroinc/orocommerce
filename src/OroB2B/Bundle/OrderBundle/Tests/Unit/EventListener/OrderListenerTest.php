<?php
namespace OroB2B\Bundle\OrderBundle\Tests\Unit\EventListener;

use OroB2B\Bundle\OrderBundle\EventListener\OrderListener;

class OrderListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testPostPersist()
    {
        $generatorMock = $this->getMock('OroB2B\Bundle\OrderBundle\Doctrine\ORM\Id\EntityAwareGeneratorInterface');
        $generatorMock->expects($this->once())
            ->method('generate')
            ->will($this->returnValue(125));
        $listener = new OrderListener($generatorMock);
        $orderMock = $this->getMock('OroB2B\Bundle\OrderBundle\Entity\Order');
        $orderMock->expects($this->once())
            ->method('setIdentifier')
            ->with(125);
        $lifecycleEventArgs = $this->getMockBuilder('Doctrine\ORM\Event\LifecycleEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $lifecycleEventArgs->expects($this->once())
            ->method('getEntity')
            ->willReturn($orderMock);
        $listener->postPersist($lifecycleEventArgs);
    }
}
