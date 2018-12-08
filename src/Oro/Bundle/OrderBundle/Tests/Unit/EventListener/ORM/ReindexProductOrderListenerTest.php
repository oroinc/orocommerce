<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\EventListener\ORM\ReindexProductOrderListener;
use Oro\Bundle\OrderBundle\Provider\OrderStatusesProviderInterface;
use Oro\Bundle\OrderBundle\Provider\PreviouslyPurchasedOrderStatusesProvider;
use Oro\Bundle\OrderBundle\Tests\Unit\EventListener\ORM\Stub\OrderStub;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Search\Reindex\ProductReindexManager;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\Entity\Stub\StubEnumValue;
use Oro\Component\Testing\Unit\EntityTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ReindexProductOrderListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    const WEBSITE_ID = 333;

    /** @var PreUpdateEventArgs|\PHPUnit\Framework\MockObject\MockObject */
    protected $event;

    /** @var ProductReindexManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $reindexManager;

    /** @var ReindexProductOrderListener */
    protected $listener;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject  */
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
        $statusProvider = new PreviouslyPurchasedOrderStatusesProvider();
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->order = $this->getEntity(OrderStub::class);
        $this->order->setInternalStatus(
            new StubEnumValue(
                OrderStatusesProviderInterface::INTERNAL_STATUS_CLOSED,
                OrderStatusesProviderInterface::INTERNAL_STATUS_CLOSED
            )
        );

        $this->website = $this->getEntity(Website::class, ['id' => self::WEBSITE_ID]);

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

        $this->doMockMethodHasChangedField(
            ReindexProductOrderListener::ORDER_INTERNAL_STATUS_FIELD,
            false
        );

        $this->reindexManager->expects($this->never())
            ->method('reindexProducts');

        $this->order->setInternalStatus(new StubEnumValue(2, ''));
        $this->listener->processOrderUpdate($this->order, $this->event);
    }

    /**
     * @dataProvider testOrderStatusChangedProvider
     *
     * @param string $oldStatusId
     * @param string $newStatusId
     * @param string $expectThatReindexEventWilBeCalled
     */
    public function testOrderStatusChanged($oldStatusId, $newStatusId, $expectThatReindexEventWilBeCalled)
    {
        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->with('previously_purchased_products', $this->website)
            ->willReturn(true);

        $this->prepareEventMockForStatusChanges($oldStatusId, $newStatusId);

        $this->reindexManager
            ->expects($expectThatReindexEventWilBeCalled ? $this->exactly(2) : $this->never())
            ->method('reindexProducts');

        $this->order->setWebsite($this->website);
        $this->listener->processOrderUpdate($this->order, $this->event);
    }

    /**
     * @return array
     */
    public function testOrderStatusChangedProvider()
    {
        return [
            'Test that order status changed from available to unavailable status' => [
                'oldStatusId' => OrderStatusesProviderInterface::INTERNAL_STATUS_SHIPPED,
                'newStatusId' => OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED,
                'expectThatReindexEventWilBeCalled' => true
            ],
            'Test that order status changed from unavailable to available status' => [
                'oldStatusId' => null,
                'newStatusId' => OrderStatusesProviderInterface::INTERNAL_STATUS_SHIPPED,
                'expectThatReindexEventWilBeCalled' => true
            ],
            'Test that order status changed from available to available status' => [
                'oldStatusId' => OrderStatusesProviderInterface::INTERNAL_STATUS_SHIPPED,
                'newStatusId' => OrderStatusesProviderInterface::INTERNAL_STATUS_CLOSED,
                'expectThatReindexEventWilBeCalled' => false
            ],
            'Test that order status changed from unavailable to unavailable status' => [
                'oldStatusId' => 'some unavailable status',
                'newStatusId' => OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED,
                'expectThatReindexEventWilBeCalled' => false
            ],
        ];
    }

    public function testOrderStatusChangedButOrderHasInvalidWebsite()
    {
        $this->doMockMethodHasChangedField(
            ReindexProductOrderListener::ORDER_INTERNAL_STATUS_FIELD,
            true
        );

        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->with('previously_purchased_products', $this->website)
            ->willReturn(true);

        $this->prepareEventMockForStatusChanges(
            OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN,
            OrderStatusesProviderInterface::INTERNAL_STATUS_CLOSED
        );

        $this->reindexManager
            ->expects($this->never())
            ->method('reindexProducts');

        $this->listener->processOrderUpdate($this->order, $this->event);
    }

    public function testOrderStatusChangedButFeatureDisabled()
    {
        $this->doMockMethodHasChangedField(
            ReindexProductOrderListener::ORDER_INTERNAL_STATUS_FIELD,
            true
        );

        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->with('previously_purchased_products', $this->website)
            ->willReturn(false);

        $this->prepareEventMockForStatusChanges(
            OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN,
            OrderStatusesProviderInterface::INTERNAL_STATUS_CLOSED
        );

        $this->reindexManager->expects($this->never())->method('reindexProducts');

        $this->order->setWebsite($this->website);

        $this->listener->processOrderUpdate($this->order, $this->event);
    }

    public function testOrderRemoved()
    {
        $productIds = [1,2,3];
        $parentProductIds = [7,9];
        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->with('previously_purchased_products', $this->website)
            ->willReturn(true);

        /** @var Product $parentProduct1 */
        $parentProduct1 = $this->getEntity(Product::class, ['id' => 7]);
        /** @var Product $parentProduct2 */
        $parentProduct2 = $this->getEntity(Product::class, ['id' => 9]);

        $product4 = $this->getEntity(Product::class, ['id' => 4]);
        $product5 = $this->getEntity(Product::class, ['id' => 5]);
        $parentProductLineItems = [
            (new OrderLineItem())->setParentProduct($parentProduct1)->setProduct($product4),
            (new OrderLineItem())->setParentProduct($parentProduct2)->setProduct($product5)
        ];

        $lineItems = new ArrayCollection(
            array_merge($this->prepareLineItemsOnOrder($productIds), $parentProductLineItems)
        );

        $this->order->setLineItems($lineItems);
        $this->order->setWebsite($this->website);

        $expectedProductIds = [1,2,3,4,5];
        $this->reindexManager
            ->expects($this->exactly(2))
            ->method('reindexProducts')
            ->withConsecutive([$expectedProductIds, self::WEBSITE_ID], [$parentProductIds, self::WEBSITE_ID]);

        $this->listener->processOrderRemove($this->order);
    }

    public function testOrderRemovedButItHasInvalidWebsite()
    {
        $productIds = [1,2,3];
        $lineItems = new ArrayCollection($this->prepareLineItemsOnOrder($productIds));
        $this->order->setLineItems($lineItems);

        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->with('previously_purchased_products')
            ->willReturn(true);

        $this->reindexManager
            ->expects($this->never())
            ->method('reindexProducts');

        $this->listener->processOrderRemove($this->order);
    }

    public function testReindexWhenFeatureDisabled()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('previously_purchased_products', $this->website)
            ->willReturn(false);

        $this->reindexManager->expects($this->never())
            ->method('reindexProducts');

        $this->order->setWebsite($this->website);
        $this->listener->processOrderRemove($this->order);
    }

    public function testReindexOnOrderWebsiteChange()
    {
        $this->doMockMethodHasChangedField(
            ReindexProductOrderListener::ORDER_INTERNAL_WEBSITE_FIELD,
            true
        );

        $website2 = $this->getEntity(Website::class, [
            'id' => 2
        ]);

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
            ->withConsecutive(
                [$this->equalTo('previously_purchased_products'), $this->website],
                [$this->equalTo('previously_purchased_products'), $website2]
            )
            ->willReturn(true);

        $this->order->setWebsite($this->website);

        $this->reindexManager
            ->expects($this->exactly(4))
            ->method('reindexProducts');

        $this->listener->processOrderUpdate($this->order, $this->event);
    }

    /**
     * @dataProvider testBaseListenerDataProvider
     *
     * @param bool   $isFieldChanged
     * @param bool   $isWebsiteSet
     * @param bool   $isFeatureEnabled
     * @param string $orderStatus
     * @param bool   $expectedReindexProductsCalled
     */
    public function testReindexOnOrderCustomerUserChange(
        $isFieldChanged,
        $isWebsiteSet,
        $isFeatureEnabled,
        $orderStatus,
        $expectedReindexProductsCalled
    ) {
        $this->doMockMethodHasChangedField(
            ReindexProductOrderListener::ORDER_CUSTOMER_USER_FIELD,
            $isFieldChanged
        );

        $this->setupAssertOnBaseListener(
            $isWebsiteSet,
            $isFeatureEnabled,
            $orderStatus,
            $expectedReindexProductsCalled
        );

        $this->listener->processOrderUpdate($this->order, $this->event);
    }

    /**
     * @dataProvider testBaseListenerDataProvider
     *
     * @param bool   $isFieldChanged
     * @param bool   $isWebsiteSet
     * @param bool   $isFeatureEnabled
     * @param string $orderStatus
     * @param bool   $expectedReindexProductsCalled
     */
    public function testReindexOnOrderCreatedAtChange(
        $isFieldChanged,
        $isWebsiteSet,
        $isFeatureEnabled,
        $orderStatus,
        $expectedReindexProductsCalled
    ) {
        $this->doMockMethodHasChangedField(
            ReindexProductOrderListener::ORDER_CREATED_AT_FIELD,
            $isFieldChanged
        );

        $this->setupAssertOnBaseListener(
            $isWebsiteSet,
            $isFeatureEnabled,
            $orderStatus,
            $expectedReindexProductsCalled
        );

        $this->listener->processOrderUpdate($this->order, $this->event);
    }

    /**
     * @param bool   $isWebsiteSet
     * @param bool   $isFeatureEnabled
     * @param string $orderStatus
     * @param bool   $expectedReindexProductsCalled
     */
    protected function setupAssertOnBaseListener(
        $isWebsiteSet,
        $isFeatureEnabled,
        $orderStatus,
        $expectedReindexProductsCalled
    ) {
        if ($isWebsiteSet) {
            $this->order->setWebsite($this->website);
            $this->featureChecker
                ->expects($this->any())
                ->method('isFeatureEnabled')
                ->with('previously_purchased_products', $this->website)
                ->willReturn($isFeatureEnabled);
        } else {
            $this->featureChecker->expects($this->never())->method('isFeatureEnabled');
        }

        $this->order->setInternalStatus(new StubEnumValue($orderStatus, $orderStatus));

        if ($expectedReindexProductsCalled) {
            $productIds = [1,2,3];
            $lineItems = new ArrayCollection($this->prepareLineItemsOnOrder($productIds));
            $this->order->setLineItems($lineItems);

            $this->reindexManager
                ->expects($this->exactly(2))
                ->method('reindexProducts')
                ->withConsecutive([$productIds, self::WEBSITE_ID], [[], self::WEBSITE_ID]);
        } else {
            $this->reindexManager->expects($this->never())->method('reindexProducts');
        }
    }

    public function testBaseListenerDataProvider()
    {
        return [
            'Field not change' => [
                'isFieldChanged' => false,
                'isWebsiteSet' => true,
                'isFeatureEnabled' => true,
                'orderStatus' => OrderStatusesProviderInterface::INTERNAL_STATUS_CLOSED,
                'expectedReindexProductsCalled' => false
            ],
            'Field changed' => [
                'isFieldChanged' => true,
                'isWebsiteSet' => true,
                'isFeatureEnabled' => true,
                'orderStatus' => OrderStatusesProviderInterface::INTERNAL_STATUS_CLOSED,
                'expectedReindexProductsCalled' => true
            ],
            'Field changed, but website invalid' => [
                'isFieldChanged' => true,
                'isWebsiteSet' => false,
                'isFeatureEnabled' => true,
                'orderStatus' => OrderStatusesProviderInterface::INTERNAL_STATUS_CLOSED,
                'expectedReindexProductsCalled' => false
            ],
            'Field changed, but feature disabled' => [
                'isFieldChanged' => true,
                'isWebsiteSet' => true,
                'isFeatureEnabled' => false,
                'orderStatus' => OrderStatusesProviderInterface::INTERNAL_STATUS_CLOSED,
                'expectedReindexProductsCalled' => false
            ],
            'Field changed, but order in not allowed status' => [
                'isFieldChanged' => true,
                'isWebsiteSet' => true,
                'isFeatureEnabled' => true,
                'orderStatus' => OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED,
                'expectedReindexProductsCalled' => false
            ]
        ];
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

    /**
     * @param string $oldStatusId
     * @param string $newStatusId
     */
    protected function prepareEventMockForStatusChanges($oldStatusId, $newStatusId)
    {
        $this->doMockMethodHasChangedField(
            ReindexProductOrderListener::ORDER_INTERNAL_STATUS_FIELD,
            true
        );

        $this->event->expects($this->once())
            ->method('getOldValue')
            ->with(ReindexProductOrderListener::ORDER_INTERNAL_STATUS_FIELD)
            ->willReturn(new StubEnumValue(
                $oldStatusId,
                $oldStatusId
            ));

        $this->event->expects($this->once())
            ->method('getNewValue')
            ->with(ReindexProductOrderListener::ORDER_INTERNAL_STATUS_FIELD)
            ->willReturn(new StubEnumValue(
                $newStatusId,
                $newStatusId
            ));
    }

    /**
     * @param string $fieldName
     * @param bool   $returnValue
     */
    protected function doMockMethodHasChangedField($fieldName, $returnValue)
    {
        $this->event
            ->method('hasChangedField')
            ->willReturnCallback(
                function ($changedFieldName) use ($fieldName, $returnValue) {
                    if ($changedFieldName === $fieldName) {
                        return $returnValue;
                    }

                    return false;
                }
            );
    }
}
