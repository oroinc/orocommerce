<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Api\Form\EventListener;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Api\Form\EventListener\DefaultWebsiteListener;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

class DefaultWebsiteListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WebsiteManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $websiteManagerMock;

    /**
     * @var DefaultWebsiteListener
     */
    private $listener;

    public function setUp()
    {
        $this->websiteManagerMock = $this->createMock(WebsiteManager::class);

        $this->listener = new DefaultWebsiteListener($this->websiteManagerMock);
    }

    /**
     * @return FormInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createFormMock()
    {
        return $this->createMock(FormInterface::class);
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
                FormEvents::SUBMIT => ['onSubmit', 10],
            ],
            DefaultWebsiteListener::getSubscribedEvents()
        );
    }

    public function testOnSubmit()
    {
        $formMock = $this->createFormMock();
        $websiteMock = $this->createWebsiteMock();
        $orderMock = $this->createOrderMock();

        $this->websiteManagerMock->expects(self::once())
            ->method('getDefaultWebsite')
            ->willReturn($websiteMock);

        $orderMock->expects(self::once())
            ->method('getWebsite')
            ->willReturn(null);

        $orderMock->expects(self::once())
            ->method('setWebsite')
            ->with($websiteMock);

        $this->listener->onSubmit(new FormEvent($formMock, $orderMock));
    }

    public function testWrongData()
    {
        $formMock = $this->createFormMock();

        $this->websiteManagerMock->expects(self::never())
            ->method('getDefaultWebsite');

        $this->listener->onSubmit(new FormEvent($formMock, null));
    }

    public function testWebsiteAlreadySet()
    {
        $formMock = $this->createFormMock();
        $websiteMock = $this->createWebsiteMock();
        $orderMock = $this->createOrderMock();

        $orderMock->expects(self::once())
            ->method('getWebsite')
            ->willReturn($websiteMock);

        $orderMock->expects(self::never())
            ->method('setWebsite')
            ->with($websiteMock);

        $this->listener->onSubmit(new FormEvent($formMock, $orderMock));
    }
}
