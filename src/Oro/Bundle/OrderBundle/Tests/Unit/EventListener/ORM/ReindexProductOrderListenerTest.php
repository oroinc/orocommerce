<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\ORM;

use Doctrine\ORM\Event\PreUpdateEventArgs;

use Oro\Bundle\OrderBundle\EventListener\ORM\ReindexProductOrderListener;
use Oro\Bundle\OrderBundle\Tests\Unit\EventListener\ORM\Stub\OrderStub;
use Oro\Bundle\ProductBundle\Manager\ProductReindexManager;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\Entity\Stub\StubEnumValue;
use Oro\Component\Testing\Unit\EntityTrait;

class ReindexProductOrderListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var PreUpdateEventArgs|\PHPUnit_Framework_MockObject_MockObject */
    protected $event;

    /** @var ProductReindexManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $reindexManager;

    /** @var ReindexProductOrderListener */
    protected $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->event = $this->createMock(PreUpdateEventArgs::class);
        $this->reindexManager = $this->createMock(ProductReindexManager::class);

        $this->listener = new ReindexProductOrderListener($this->reindexManager);
    }

    public function testOrderStatusNotChanged()
    {
        $this->event->expects($this->once())
            ->method('hasChangedField')
            ->with(ReindexProductOrderListener::ORDER_INTERNAL_STATUS_FIELD)
            ->willReturn(false);

        $this->reindexManager->expects($this->never())
            ->method('triggerReindexationRequestEvent');

        $order = $this->getEntity(OrderStub::class, ['internalStatus' => new StubEnumValue(2, '')]);
        $this->listener->processIndexOnOrderStatusChange($order, $this->event);
    }

    public function testOrderStatusNotArchivedOrClosed()
    {
        $this->event->expects($this->once())
            ->method('hasChangedField')
            ->with(ReindexProductOrderListener::ORDER_INTERNAL_STATUS_FIELD)
            ->willReturn(true);

        $this->reindexManager->expects($this->never())
            ->method('triggerReindexationRequestEvent');

        $order = $this->getEntity(OrderStub::class, ['internalStatus' => new StubEnumValue(1, 'open')]);
        $this->listener->processIndexOnOrderStatusChange($order, $this->event);
    }

    public function testOrderStatusArchivedOrClosed()
    {
        $this->event->expects($this->once())
            ->method('hasChangedField')
            ->with(ReindexProductOrderListener::ORDER_INTERNAL_STATUS_FIELD)
            ->willReturn(true);

        $website = $this->createMock(Website::class);
        $website->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $this->reindexManager->expects($this->once())
            ->method('triggerReindexationRequestEvent');

        $order = $this->getEntity(OrderStub::class, ['internalStatus' => new StubEnumValue('archived', 1)]);
        $order->setWebsite($website);
        $this->listener->processIndexOnOrderStatusChange($order, $this->event);
    }
}
