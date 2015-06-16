<?php
namespace OroB2B\Bundle\OrderBundle\Tests\Unit\EventListener;

use OroB2B\Bundle\OrderBundle\Doctrine\ORM\Id\SimpleEntityAwareGenerator;
use OroB2B\Bundle\OrderBundle\EventListener\OrderListener;

class OrderListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testPostPersist()
    {
        $generator = new SimpleEntityAwareGenerator();
        $listener = new OrderListener($generator);
        $resultingIdentifier = null;
        $orderMock = $this->getMock('OroB2B\Bundle\OrderBundle\Entity\Order');
        $orderMock->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(125));
        $orderMock->expects($this->once())
            ->method('setIdentifier')
            ->willReturnCallback(function ($id) use (&$resultingIdentifier) {
                $resultingIdentifier = $id;
            });
        $lifecycleEventArgs = $this->getMockBuilder('Doctrine\ORM\Event\LifecycleEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $lifecycleEventArgs->expects($this->once())
            ->method('getEntity')
            ->willReturn($orderMock);
        $listener->postPersist($lifecycleEventArgs);
        $this->assertEquals(125, $resultingIdentifier);
    }
}
