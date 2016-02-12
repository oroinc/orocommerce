<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;

use OroB2B\Bundle\OrderBundle\Doctrine\ORM\Id\EntityAwareGeneratorInterface;
use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\EventListener\OrderListener;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\WebsiteBundle\Manager\WebsiteManager;

class OrderListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|EntityAwareGeneratorInterface
     */
    protected $generator;

    /**
     * @var OrderListener
     */
    protected $listener;

    /**
     * @var WebsiteManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $websiteManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->generator = $this->getMock('OroB2B\Bundle\OrderBundle\Doctrine\ORM\Id\EntityAwareGeneratorInterface');
        $this->websiteManager = $this->getMockBuilder('OroB2B\Bundle\WebsiteBundle\Manager\WebsiteManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new OrderListener($this->generator, $this->websiteManager);
    }

    /**
     * {@inheritdoc}
     */
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

    public function testPrePersist()
    {
        $website = new Website();
        $order = new Order();
        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);
        $this->listener->prePersist($this->getLifecycleEventArgs($order));
        $this->assertSame($website, $order->getWebsite());
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
