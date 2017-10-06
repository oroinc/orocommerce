<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Event\PreUpdateEventArgs;

use Oro\Component\Testing\Unit\Entity\Stub\StubEnumValue;
use Oro\Component\Testing\Unit\EntityTrait;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
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

    /** @var FeatureChecker  */
    protected $featureChecker;

    /** @var  Website */
    protected $website;

    /** @var  OrderStub */
    protected $order;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->event = $this->createMock(PreUpdateEventArgs::class);
        $this->reindexManager = $this->createMock(ProductReindexManager::class);
        $statusProvider = new PreviouslyPurchasedOrderStatusesProviderStub();
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->order = $this->getEntity(OrderStub::class);
        $this->order->setInternalStatus(new StubEnumValue('closed', 'closed'));
        $this->website = $this->createMock(Website::class);
        $this->website->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $this->listener = new ReindexProductOrderListener(
            $this->reindexManager,
            $statusProvider
        );

        $this->listener->setFeatureChecker($this->featureChecker);
        $this->listener->addFeature('previously_purchased_products');
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->listener);
        unset($this->website);
        unset($this->event);
        unset($this->reindexManager);
        unset($this->featureChecker);
    }

    public function testOrderStatusNotChanged()
    {
        $this->featureChecker->expects($this->never())
            ->method('isFeatureEnabled')
            ->with('previously_purchased_products', $this->website)
            ->willReturn(true);
        $this->event->expects($this->once())
            ->method('hasChangedField')
            ->with(ReindexProductOrderListener::ORDER_INTERNAL_STATUS_FIELD)
            ->willReturn(false);

        $this->reindexManager->expects($this->never())
            ->method('triggerReindexationRequestEvent');

        $this->order->setInternalStatus(new StubEnumValue(2, ''));
        $this->listener->processOrderUpdate($this->order, $this->event);
    }

    /**
     * @dataProvider testOrderStatusChangedProvider
     *
     * @param string $getOldValue
     * @param string $getNewValue
     * @param string $expectThatReindexEventWilBeCalled
     */
    public function testOrderStatusNotArchivedOrClosed($getOldValue, $getNewValue, $expectThatReindexEventWilBeCalled)
    {
        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->with('previously_purchased_products', $this->website)
            ->willReturn(true);

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
        $this->listener->processOrderUpdate($order, $this->event);
    }

    /**
     * @return array
     */
    public function testOrderStatusChangedProvider()
    {
        return [
            'Test that order change from trackable to untrackable status' => [
                'getOldValue' => new StubEnumValue(
                    OrderStatusesProviderInterface::INTERNAL_STATUS_SHIPPED,
                    OrderStatusesProviderInterface::INTERNAL_STATUS_SHIPPED
                ),
                'getNewValue' => new StubEnumValue(
                    OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED,
                    OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED
                ),
                'expectThatReindexEventWilBeCalled' => true
            ],
            'Test that order change from untrackable to trackable status' => [
                'getOldValue' => null,
                'getNewValue' => new StubEnumValue(
                    OrderStatusesProviderInterface::INTERNAL_STATUS_SHIPPED,
                    OrderStatusesProviderInterface::INTERNAL_STATUS_SHIPPED
                ),
                'expectThatReindexEventWilBeCalled' => true
            ],
            'Test that order change from trackable to trackable status' => [
                'getOldValue' => new StubEnumValue(
                    OrderStatusesProviderInterface::INTERNAL_STATUS_SHIPPED,
                    OrderStatusesProviderInterface::INTERNAL_STATUS_SHIPPED
                ),
                'getNewValue' => new StubEnumValue(
                    OrderStatusesProviderInterface::INTERNAL_STATUS_CLOSED,
                    OrderStatusesProviderInterface::INTERNAL_STATUS_CLOSED
                ),
                'expectThatReindexEventWilBeCalled' => false
            ],
            'Test that order change from untrackable to untrackable status' => [
                'getOldValue' => new StubEnumValue(
                    OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN,
                    OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN
                ),
                'getNewValue' => new StubEnumValue(
                    OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED,
                    OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED
                ),
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
            ->willReturn(new StubEnumValue(
                OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN,
                OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN
            ));

        $this->event->expects($this->once())
            ->method('getNewValue')
            ->with(ReindexProductOrderListener::ORDER_INTERNAL_STATUS_FIELD)
            ->willReturn(new StubEnumValue(
                OrderStatusesProviderInterface::INTERNAL_STATUS_CLOSED,
                OrderStatusesProviderInterface::INTERNAL_STATUS_CLOSED
            ));

        $this->reindexManager->expects($this->never())
            ->method('triggerReindexationRequestEvent');

        $order = $this->getEntity(OrderStub::class);
        $this->listener->processOrderUpdate($order, $this->event);
    }

    public function testOrderRemoved()
    {
        $productIds = [1,2,3];
        $websiteId = 1;
        $website = $this->createMock(Website::class);
        $website->method('getId')->willReturn($websiteId);
        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->with('previously_purchased_products', $website)
            ->willReturn(true);

        $lineItems = new ArrayCollection($this->prepareLineItemsOnOrder($productIds));
        $this->order->setLineItems($lineItems);
        $this->order->setWebsite($website);

        $this->reindexManager
            ->expects($this->once())
            ->method('triggerReindexationRequestEvent')
            ->with($productIds, $websiteId);

        $this->listener->processOrderRemove($this->order);
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

        $this->listener->processOrderRemove($order);
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

    public function testReindexWhenFeatureDisabled()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('previously_purchased_products', $this->website)
            ->willReturn(false);

        $this->reindexManager->expects($this->never())
            ->method('triggerReindexationRequestEvent');

        $this->order->setWebsite($this->website);
        $this->listener->processOrderRemove($this->order);
    }

    public function testReindexOnOrderWebsiteChange()
    {
        $this->event->expects($this->once())
            ->method('hasChangedField')
            ->with(ReindexProductOrderListener::ORDER_INTERNAL_WEBSITE_FIELD)
            ->willReturn(true);

        $website2 = $this->createMock(Website::class);
        $website2->expects($this->any())
            ->method('getId')
            ->willReturn(2);

        $this->event->expects($this->once())
            ->method('getOldValue')
            ->with(ReindexProductOrderListener::ORDER_INTERNAL_WEBSITE_FIELD)
            ->willReturn($this->website);

        $this->event->expects($this->once())
            ->method('getNewValue')
            ->with(ReindexProductOrderListener::ORDER_INTERNAL_WEBSITE_FIELD)
            ->willReturn($website2);

        $this->featureChecker->expects($this->exactly(2))
            ->method('isFeatureEnabled')
            ->with('previously_purchased_products', $this->website)
            ->willReturn(true);

        $this->order->setWebsite($this->website);

        $this->reindexManager->expects($this->exactly(2))
            ->method('triggerReindexationRequestEvent');

        $this->listener->processOrderUpdate($this->order, $this->event);
    }
}
