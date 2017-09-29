<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\ORM;

use Doctrine\ORM\Event\PreUpdateEventArgs;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\EventListener\ORM\ReindexProductLineItemListener;
use Oro\Bundle\OrderBundle\Tests\Unit\EventListener\ORM\Stub\OrderStub;
use Oro\Bundle\OrderBundle\Tests\Unit\EventListener\ORM\Stub\PreviouslyPurchasedOrderStatusesProviderStub;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Manager\ProductReindexManager;
use Oro\Bundle\WebsiteBundle\Entity\Website;

use Oro\Component\Testing\Unit\Entity\Stub\StubEnumValue;
use Oro\Component\Testing\Unit\EntityTrait;

class ReindexProductLineItemListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var PreUpdateEventArgs|\PHPUnit_Framework_MockObject_MockObject */
    protected $event;

    /** @var ProductReindexManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $reindexManager;

    /** @var ReindexProductLineItemListener */
    protected $listener;

    /** @var OrderLineItem|\PHPUnit_Framework_MockObject_MockObject */
    protected $lineItem;

    /** @var  Order */
    protected $order;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->event = $this->createMock(PreUpdateEventArgs::class);
        $this->reindexManager = $this->createMock(ProductReindexManager::class);
        $statusProvider = new PreviouslyPurchasedOrderStatusesProviderStub();
        $this->lineItem = $this->createMock(OrderLineItem::class);
        $website = $this->createMock(Website::class);
        $website->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $this->order = $this->getEntity(OrderStub::class);
        $this->order->setInternalStatus(new StubEnumValue('closed', 'closed'));
        $this->order->setWebsite($website);

        $this->lineItem->expects($this->any())
            ->method('getOrder')
            ->willReturn($this->order);

        $this->listener = new ReindexProductLineItemListener($this->reindexManager, $statusProvider);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->order);
        unset($this->listener);
        unset($this->lineItem);
        unset($this->reindexManager);
        unset($this->event);
    }

    public function testOrderLineItemProductNotChanged()
    {
        $this->event->expects($this->once())
            ->method('hasChangedField')
            ->with(ReindexProductLineItemListener::ORDER_LINE_ITEM_PRODUCT_FIELD)
            ->willReturn(false);

        $this->reindexManager->expects($this->never())
            ->method('reindexProduct');

        $this->listener->reindexProductOnLineItemUpdate($this->lineItem, $this->event);
    }

    public function testOrderLineItemProductChange()
    {
        $this->setupAssertForCaseFieldChanged();

        $this->reindexManager->expects($this->exactly(2))
            ->method('reindexProduct');

        $this->listener->reindexProductOnLineItemUpdate($this->lineItem, $this->event);
    }

    public function testReindexProductOnLineItemCreateOrDelete()
    {
        $product = $this->createMock(Product::class);
        $this->lineItem->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);
        $this->reindexManager->expects($this->once())
            ->method('reindexProduct');

        $this->listener->reindexProductOnLineItemCreateOrDelete($this->lineItem, $this->event);
    }

    public function testReindexInOrderWithUnavailableStatus()
    {
        $this->setupAssertForCaseFieldChanged();
        $this->order->setInternalStatus(new StubEnumValue('open', 'open'));
        $this->reindexManager->expects($this->never())
            ->method('reindexProduct');

        $this->listener->reindexProductOnLineItemUpdate($this->lineItem, $this->event);
    }

    public function testReindexInOrderWithInvalidWebsite()
    {
        $this->setupAssertForCaseFieldChanged();
        $reflection = new \ReflectionClass($this->order);
        $websiteProperty = $reflection->getProperty('website');
        $websiteProperty->setAccessible(true);
        $websiteProperty->setValue($this->order, null);
        $this->reindexManager->expects($this->never())
            ->method('reindexProduct');

        $this->listener->reindexProductOnLineItemUpdate($this->lineItem, $this->event);
    }

    protected function setupAssertForCaseFieldChanged()
    {
        $this->event->expects($this->any())
            ->method('hasChangedField')
            ->with(ReindexProductLineItemListener::ORDER_LINE_ITEM_PRODUCT_FIELD)
            ->willReturn(true);
        $product = $this->createMock(Product::class);
        $this->event->expects($this->any())
            ->method('getOldValue')
            ->willReturn($product);
        $this->event->expects($this->any())
            ->method('getNewValue')
            ->willReturn($product);
    }
}
