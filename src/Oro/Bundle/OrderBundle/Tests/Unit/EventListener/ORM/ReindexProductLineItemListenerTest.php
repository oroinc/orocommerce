<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\ORM;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\EventListener\ORM\ReindexProductLineItemListener;
use Oro\Bundle\OrderBundle\Provider\OrderStatusesProviderInterface;
use Oro\Bundle\OrderBundle\Provider\PreviouslyPurchasedOrderStatusesProvider;
use Oro\Bundle\OrderBundle\Tests\Unit\EventListener\ORM\Stub\OrderStub;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Search\Reindex\ProductReindexManager;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\Entity\Stub\StubEnumValue;
use Oro\Component\Testing\Unit\EntityTrait;

class ReindexProductLineItemListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    const WEBSITE_ID = 1;

    /** @var PreUpdateEventArgs|\PHPUnit\Framework\MockObject\MockObject */
    protected $event;

    /** @var ProductReindexManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $reindexManager;

    /** @var ReindexProductLineItemListener */
    protected $listener;

    /** @var OrderLineItem|\PHPUnit\Framework\MockObject\MockObject */
    protected $lineItem;

    /** @var  OrderStub */
    protected $order;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject  */
    protected $featureChecker;

    /** @var  Website */
    protected $website;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->reindexManager = $this->createMock(ProductReindexManager::class);
        $statusProvider = new PreviouslyPurchasedOrderStatusesProvider();
        $this->lineItem = $this->createMock(OrderLineItem::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->website = $this->getEntity(Website::class, [ 'id' => self:: WEBSITE_ID ]);

        $this->order = $this->getEntity(OrderStub::class);
        $this->order->setInternalStatus(new StubEnumValue(
            OrderStatusesProviderInterface::INTERNAL_STATUS_CLOSED,
            OrderStatusesProviderInterface::INTERNAL_STATUS_CLOSED
        ));
        $this->order->setWebsite($this->website);

        $this->lineItem->expects($this->any())
            ->method('getOrder')
            ->willReturn($this->order);

        $this->listener = new ReindexProductLineItemListener(
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
        unset($this->order);
        unset($this->website);
        unset($this->listener);
        unset($this->lineItem);
        unset($this->reindexManager);
        unset($this->featureChecker);
    }

    public function testOrderLineItemProductNotChanged()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('previously_purchased_products', $this->website)
            ->willReturn(true);

        $event = $this->getPreUpdateEvent();

        $this->reindexManager
            ->expects($this->never())
            ->method('reindexProduct');

        $this->listener->reindexProductOnLineItemUpdate($this->lineItem, $event);
    }

    public function testOrderLineItemProductChange()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('previously_purchased_products', $this->website)
            ->willReturn(true);

        $product1 = $this->getEntity(Product::class, [ 'id' => 1 ]);
        $product2 = $this->getEntity(Product::class, [ 'id' => 2 ]);
        $event = $this->getPreUpdateEvent(
            [
                ReindexProductLineItemListener::ORDER_LINE_ITEM_PRODUCT_FIELD => [
                    $product1,
                    $product2,
                ],
            ]
        );

        $this->reindexManager
            ->expects($this->exactly(2))
            ->method('reindexProduct')
            ->withConsecutive(
                [$product1, self::WEBSITE_ID],
                [$product2, self::WEBSITE_ID]
            );

        $this->listener->reindexProductOnLineItemUpdate($this->lineItem, $event);
    }

    public function testOrderLineItemParentProductChange()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('previously_purchased_products', $this->website)
            ->willReturn(true);

        $product1 = $this->getEntity(Product::class, [ 'id' => 1 ]);
        $product2 = $this->getEntity(Product::class, [ 'id' => 2 ]);
        $event = $this->getPreUpdateEvent(
            [
                ReindexProductLineItemListener::ORDER_LINE_ITEM_PARENT_PRODUCT_FIELD => [
                    $product1,
                    $product2,
                ],
            ]
        );

        $this->reindexManager
            ->expects($this->exactly(2))
            ->method('reindexProduct')
            ->withConsecutive(
                [$product1, self::WEBSITE_ID],
                [$product2, self::WEBSITE_ID]
            );

        $this->listener->reindexProductOnLineItemUpdate($this->lineItem, $event);
    }

    public function testReindexProductOnLineItemCreateOrDelete()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('previously_purchased_products', $this->website)
            ->willReturn(true);

        $product = $this->getEntity(Product::class);

        $this->lineItem->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);

        $this->reindexManager->expects($this->once())
            ->method('reindexProduct')
            ->with($product, self::WEBSITE_ID);

        $this->listener->reindexProductOnLineItemCreateOrDelete($this->lineItem);
    }

    public function testReindexProductOnLineItemCreateOrDeleteWithParentProduct()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('previously_purchased_products', $this->website)
            ->willReturn(true);

        $product = $this->getEntity(Product::class);
        $parentProduct = $this->getEntity(Product::class);

        $this->lineItem->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);

        $this->lineItem->expects($this->atLeastOnce())
            ->method('getParentProduct')
            ->willReturn($parentProduct);

        $this->reindexManager->expects($this->exactly(2))
            ->method('reindexProduct')
            ->withConsecutive([$product, self::WEBSITE_ID], [$parentProduct, self::WEBSITE_ID]);

        $this->listener->reindexProductOnLineItemCreateOrDelete($this->lineItem);
    }

    public function testReindexInOrderWithUnavailableStatus()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('previously_purchased_products', $this->website)
            ->willReturn(true);

        $this->order->setInternalStatus(
            new StubEnumValue(
                OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED,
                OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED
            )
        );

        $product1 = $this->getEntity(Product::class, [ 'id' => 1 ]);
        $product2 = $this->getEntity(Product::class, [ 'id' => 2 ]);
        $event = $this->getPreUpdateEvent(
            [
                ReindexProductLineItemListener::ORDER_LINE_ITEM_PRODUCT_FIELD => [
                    $product1,
                    $product2,
                ],
            ]
        );

        $this->reindexManager->expects($this->never())
            ->method('reindexProduct');

        $this->listener->reindexProductOnLineItemUpdate($this->lineItem, $event);
    }

    public function testReindexInOrderWithInvalidWebsite()
    {
        $product1 = $this->getEntity(Product::class, [ 'id' => 1 ]);
        $product2 = $this->getEntity(Product::class, [ 'id' => 2 ]);
        $event = $this->getPreUpdateEvent(
            [
                ReindexProductLineItemListener::ORDER_LINE_ITEM_PRODUCT_FIELD => [
                    $product1,
                    $product2,
                ],
            ]
        );

        $this->order->unsetWebsite();

        $this->reindexManager
            ->expects($this->never())
            ->method('reindexProduct');

        $this->listener->reindexProductOnLineItemUpdate($this->lineItem, $event);
    }

    public function testFeatureDisabled()
    {
        $product1 = $this->getEntity(Product::class, [ 'id' => 1 ]);
        $product2 = $this->getEntity(Product::class, [ 'id' => 2 ]);
        $event = $this->getPreUpdateEvent(
            [
                ReindexProductLineItemListener::ORDER_LINE_ITEM_PRODUCT_FIELD => [
                    $product1,
                    $product2,
                ],
            ]
        );

        $this->featureChecker->expects($this->exactly(2))
            ->method('isFeatureEnabled')
            ->with('previously_purchased_products', $this->website)
            ->willReturn(false);

        $this->reindexManager
            ->expects($this->never())
            ->method('reindexProduct');

        $this->listener->reindexProductOnLineItemUpdate($this->lineItem, $event);
        $this->listener->reindexProductOnLineItemCreateOrDelete($this->lineItem);
    }

    /**
     * @param array       $changeSet
     *
     * @return PreUpdateEventArgs
     */
    protected function getPreUpdateEvent(array $changeSet = [])
    {
        /**
         * @var $em EntityManagerInterface
         */
        $em = $this->createMock(EntityManagerInterface::class);
        return new PreUpdateEventArgs(
            $this->getEntity(OrderLineItem::class),
            $em,
            $changeSet
        );
    }
}
