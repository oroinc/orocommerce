<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Type\EventListener;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Form\Type\EventListener\DefaultWebsiteSubscriber;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class DefaultWebsiteSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WebsiteManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $websiteManagerMock;

    /**
     * @var DefaultWebsiteSubscriber
     */
    private $testedProcessor;

    public function setUp()
    {
        $this->websiteManagerMock = $this->createMock(WebsiteManager::class);

        $this->testedProcessor = new DefaultWebsiteSubscriber($this->websiteManagerMock);
    }

    /**
     * @return FormEvent|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createEventMock()
    {
        return $this->createMock(FormEvent::class);
    }

    /**
     * @return Website|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createWebsiteMock()
    {
        return $this->createMock(Website::class);
    }

    /**
     * @return Order|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createOrderMock()
    {
        return $this->createMock(Order::class);
    }

    public function testGetSubscribedEvents()
    {
        $this->assertEquals(
            [
                FormEvents::SUBMIT => ['onSubmitEventListener', 10],
            ],
            DefaultWebsiteSubscriber::getSubscribedEvents()
        );
    }

    public function testOnSubmitEventListener()
    {
        $eventMock = $this->createEventMock();
        $websiteMock = $this->createWebsiteMock();
        $orderMock = $this->createOrderMock();

        $eventMock
            ->expects(static::once())
            ->method('getData')
            ->willReturn($orderMock);

        $this->websiteManagerMock
            ->expects(static::once())
            ->method('getDefaultWebsite')
            ->willReturn($websiteMock);

        $orderMock
            ->expects(static::once())
            ->method('getWebsite')
            ->willReturn(null);

        $orderMock
            ->expects(static::once())
            ->method('setWebsite')
            ->with($websiteMock);

        $eventMock
            ->expects(static::once())
            ->method('setData')
            ->with($orderMock);

        $this->testedProcessor->onSubmitEventListener($eventMock);
    }

    public function testWrongData()
    {
        $eventMock = $this->createEventMock();

        $eventMock
            ->expects(static::once())
            ->method('getData')
            ->willReturn(null);

        $eventMock
            ->expects(static::never())
            ->method('setData');

        $this->testedProcessor->onSubmitEventListener($eventMock);
    }

    public function testWebsiteAlreadySet()
    {
        $eventMock = $this->createEventMock();
        $websiteMock = $this->createWebsiteMock();
        $orderMock = $this->createOrderMock();

        $eventMock
            ->expects(static::once())
            ->method('getData')
            ->willReturn($orderMock);

        $orderMock
            ->expects(static::once())
            ->method('getWebsite')
            ->willReturn($websiteMock);

        $orderMock
            ->expects(static::never())
            ->method('setWebsite')
            ->with($websiteMock);

        $eventMock
            ->expects(static::never())
            ->method('setData')
            ->with($orderMock);

        $this->testedProcessor->onSubmitEventListener($eventMock);
    }
}
