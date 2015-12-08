<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

use OroB2B\Bundle\ProductBundle\EventListener\ProductDataStorageEventListener;
use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;

class ProductDataStorageEventListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ProductDataStorageEventListener */
    protected $listener;

    /** @var ProductDataStorage|\PHPUnit_Framework_MockObject_MockObject */
    protected $productDataStorage;

    protected function setUp()
    {
        $this->productDataStorage = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new ProductDataStorageEventListener($this->productDataStorage);
    }

    public function testHasParameter()
    {
        /** @var GetResponseEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder('Symfony\Component\HttpKernel\Event\GetResponseEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())->method('getRequest')->willReturn(new Request(['storage' => true]));

        $this->productDataStorage->expects($this->never())->method($this->anything());

        $this->listener->onKernelRequest($event);
    }

    public function testStorageIsUsed()
    {
        /** @var GetResponseEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder('Symfony\Component\HttpKernel\Event\GetResponseEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())->method('getRequest')->willReturn(new Request());

        $this->productDataStorage->expects($this->once())->method('isInvoked')->willReturn(true);
        $this->productDataStorage->expects($this->never())->method('remove');

        $this->listener->onKernelRequest($event);
    }

    public function testRemoveIfNotUsed()
    {
        /** @var GetResponseEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder('Symfony\Component\HttpKernel\Event\GetResponseEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())->method('getRequest')->willReturn(new Request());

        $this->productDataStorage->expects($this->once())->method('isInvoked')->willReturn(false);
        $this->productDataStorage->expects($this->once())->method('remove');

        $this->listener->onKernelRequest($event);
    }
}
