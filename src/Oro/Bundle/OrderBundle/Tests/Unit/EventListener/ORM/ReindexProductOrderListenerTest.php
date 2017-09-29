<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Event\PreUpdateEventArgs;

use Oro\Component\Testing\Unit\EntityTrait;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\EventListener\ORM\ReindexProductOrderListener;
use Oro\Bundle\OrderBundle\Provider\OrderStatusesProviderInterface;
use Oro\Bundle\OrderBundle\Tests\Unit\EventListener\ORM\Stub\OrderStub;
use Oro\Bundle\OrderBundle\Tests\Unit\EventListener\ORM\Stub\PreviouslyPurchasedOrderStatusesProviderStub;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Manager\ProductReindexManager;
use Oro\Bundle\WebsiteBundle\Entity\Website;

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
        $statusProvider = new PreviouslyPurchasedOrderStatusesProviderStub();

        $this->listener = new ReindexProductOrderListener($this->reindexManager, $statusProvider);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->listener);
        unset($this->event);
        unset($this->reindexManager);
    }

    public function testOrderStatusNotChanged()
    {
        $this->event->expects($this->once())
            ->method('hasChangedField')
            ->with(ReindexProductOrderListener::ORDER_INTERNAL_STATUS_FIELD)
            ->willReturn(false);

        $this->reindexManager->expects($this->never())
            ->method('triggerReindexationRequestEvent');

        $order = $this->getEntity(OrderStub::class);
        $this->listener->processIndexOnOrderStatusChange($order, $this->event);
    }

    /**
     * @dataProvider testOrderStatusChangedProvider
     *
     * @param string $getOldValue
     * @param string $getNewValue
     * @param string $expectThatReindexEventWilBeCalled
     */
    public function testOrderStatusChanged($getOldValue, $getNewValue, $expectThatReindexEventWilBeCalled)
    {
        $this->event->expects($this->once())
            ->method('hasChangedField')
            ->with(ReindexProductOrderListener::ORDER_INTERNAL_STATUS_FIELD)
            ->willReturn(true);

        $this->event->expects($this->once())
            ->method('getOldValue')
            ->with(ReindexProductOrderListener::ORDER_INTERNAL_STATUS_FIELD)
            ->willReturn($getOldValue);

        $this->event->expects($this->once())
            ->method('getNewValue')
            ->with(ReindexProductOrderListener::ORDER_INTERNAL_STATUS_FIELD)
            ->willReturn($getNewValue);

        $this->reindexManager
            ->expects($expectThatReindexEventWilBeCalled ? $this->once() : $this->never())
            ->method('triggerReindexationRequestEvent');

        $website = $this->createMock(Website::class);
        $order = $this->getEntity(OrderStub::class);
        $order->setWebsite($website);
        $this->listener->processIndexOnOrderStatusChange($order, $this->event);
    }

    /**
     * @return array
     */
    public function testOrderStatusChangedProvider()
    {
        return [
            'Test that order change from trackable to untrackable status' => [
                'getOldValue' => OrderStatusesProviderInterface::INTERNAL_STATUS_SHIPPED,
                'getNewValue' => OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED,
                'expectThatReindexEventWilBeCalled' => true
            ],
            'Test that order change from untrackable to trackable status' => [
                'getOldValue' => OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN,
                'getNewValue' => OrderStatusesProviderInterface::INTERNAL_STATUS_SHIPPED,
                'expectThatReindexEventWilBeCalled' => true
            ],
            'Test that order change from trackable to trackable status' => [
                'getOldValue' => OrderStatusesProviderInterface::INTERNAL_STATUS_SHIPPED,
                'getNewValue' => OrderStatusesProviderInterface::INTERNAL_STATUS_CLOSED,
                'expectThatReindexEventWilBeCalled' => false
            ],
            'Test that order change from untrackable to untrackable status' => [
                'getOldValue' => OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN,
                'getNewValue' => OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED,
                'expectThatReindexEventWilBeCalled' => false
            ],
        ];
    }

    public function testOrderStatusChangedButOrderHasInvalidWebsite()
    {
        $this->event->expects($this->any())
            ->method('hasChangedField')
            ->with(ReindexProductOrderListener::ORDER_INTERNAL_STATUS_FIELD)
            ->willReturn(true);

        $this->event->expects($this->once())
            ->method('getOldValue')
            ->with(ReindexProductOrderListener::ORDER_INTERNAL_STATUS_FIELD)
            ->willReturn(OrderStatusesProviderInterface::INTERNAL_STATUS_SHIPPED);

        $this->event->expects($this->once())
            ->method('getNewValue')
            ->with(ReindexProductOrderListener::ORDER_INTERNAL_STATUS_FIELD)
            ->willReturn(OrderStatusesProviderInterface::INTERNAL_STATUS_CLOSED);

        $this->reindexManager
            ->expects($this->never())
            ->method('triggerReindexationRequestEvent');

        $order = $this->getEntity(OrderStub::class);
        $this->listener->processIndexOnOrderStatusChange($order, $this->event);
    }

    public function testOrderRemoved()
    {
        $productIds = [1,2,3];
        $websiteId = 1;
        $website = $this->createMock(Website::class);
        $website->method('getId')->willReturn($websiteId);
        $order = $this->getEntity(OrderStub::class);
        $order->setWebsite($website);
        $lineItems = new ArrayCollection($this->prepareLineItemsOnOrder($productIds));
        $order->setLineItems($lineItems);

        $this->reindexManager
            ->expects($this->once())
            ->method('triggerReindexationRequestEvent')
            ->with($productIds, $websiteId);

        $this->listener->reindexProductsInOrder($order);
    }

    public function testOrderRemovedButItHasInvalidWebsite()
    {
        $productIds = [1,2,3];
        $websiteId = 1;
        $order = $this->getEntity(OrderStub::class);
        $lineItems = new ArrayCollection($this->prepareLineItemsOnOrder($productIds));
        $order->setLineItems($lineItems);

        $this->reindexManager
            ->expects($this->never())
            ->method('triggerReindexationRequestEvent')
            ->with($productIds, $websiteId);

        $this->listener->reindexProductsInOrder($order);
    }

    /**
     * @param array $productIds
     *
     * @return array
     */
    protected function prepareLineItemsOnOrder(array $productIds)
    {
        return array_map(function ($productId) {
            $product = $this->createMock(Product::class);
            $product->method('getId')->willReturn($productId);

            $lineItem = $this->createMock(OrderLineItem::class);
            $lineItem->method('getProduct')->willReturn($product);

            return $lineItem;
        }, $productIds);
    }
}
