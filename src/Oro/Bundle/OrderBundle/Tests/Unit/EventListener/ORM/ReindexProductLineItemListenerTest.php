<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\ORM;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\EventListener\ORM\ReindexProductLineItemListener;
use Oro\Bundle\OrderBundle\Provider\OrderStatusesProviderInterface;
use Oro\Bundle\OrderBundle\Provider\PreviouslyPurchasedOrderStatusesProvider;
use Oro\Bundle\OrderBundle\Tests\Unit\EventListener\ORM\Stub\OrderStub;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Oro\Bundle\WebsiteSearchBundle\Provider\ReindexationWebsiteProviderInterface;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ReindexProductLineItemListenerTest extends \PHPUnit\Framework\TestCase
{
    private const WEBSITE_ID = 10;
    private const ANOTHER_WEBSITE_ID = 20;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var Website */
    private $website;

    /** @var OrderStub */
    private $order;

    /** @var OrderLineItem|\PHPUnit\Framework\MockObject\MockObject */
    private $lineItem;

    /** @var ReindexProductLineItemListener */
    private $listener;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->website = $this->getWebsite(self::WEBSITE_ID);

        $this->order = new OrderStub();
        $this->order->setInternalStatus(
            $this->getInternalStatus(OrderStatusesProviderInterface::INTERNAL_STATUS_CLOSED)
        );
        $this->order->setWebsite($this->website);

        $this->lineItem = $this->createMock(OrderLineItem::class);
        $this->lineItem->expects(self::any())
            ->method('getOrder')
            ->willReturn($this->order);

        $reindexationWebsiteProvider = $this->createMock(ReindexationWebsiteProviderInterface::class);
        $reindexationWebsiteProvider->expects(self::any())
            ->method('getReindexationWebsiteIds')
            ->willReturnCallback(function (Website $website) {
                return [$website->getId(), $website->getId() + 1, self::ANOTHER_WEBSITE_ID];
            });

        $this->listener = new ReindexProductLineItemListener(
            $this->eventDispatcher,
            new PreviouslyPurchasedOrderStatusesProvider(),
            $reindexationWebsiteProvider
        );
        $this->listener->setFeatureChecker($this->featureChecker);
        $this->listener->addFeature('previously_purchased_products');
    }

    private function getPreUpdateEvent(array $changeSet): PreUpdateEventArgs
    {
        return new PreUpdateEventArgs(
            $this->lineItem,
            $this->createMock(EntityManagerInterface::class),
            $changeSet
        );
    }

    private function getWebsite(int $id): Website
    {
        $website = new Website();
        ReflectionUtil::setId($website, $id);

        return $website;
    }

    private function getProduct(int $id): Product
    {
        $product = new Product();
        ReflectionUtil::setId($product, $id);

        return $product;
    }

    private function getInternalStatus(?string $id): AbstractEnumValue
    {
        return new TestEnumValue($id, null !== $id ? sprintf('Status (%s)', $id) : null);
    }

    public function testOrderLineItemProductNotChanged(): void
    {
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('previously_purchased_products', $this->website)
            ->willReturn(true);

        $this->eventDispatcher->expects(self::never())
            ->method('dispatch');

        $event = $this->getPreUpdateEvent([]);
        $this->listener->reindexProductOnLineItemUpdate($this->lineItem, $event);
    }

    public function testOrderLineItemProductChange(): void
    {
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('previously_purchased_products', $this->website)
            ->willReturn(true);

        $product1 = $this->getProduct(1);
        $product2 = $this->getProduct(2);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                new ReindexationRequestEvent(
                    [Product::class],
                    [self::WEBSITE_ID, self::WEBSITE_ID + 1, self::ANOTHER_WEBSITE_ID],
                    [$product1->getId(), $product2->getId()],
                    true,
                    ['order']
                ),
                ReindexationRequestEvent::EVENT_NAME
            );

        $event = $this->getPreUpdateEvent(['product' => [$product1, $product2]]);
        $this->listener->reindexProductOnLineItemUpdate($this->lineItem, $event);
    }

    public function testOrderLineItemParentProductChange(): void
    {
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('previously_purchased_products', $this->website)
            ->willReturn(true);

        $product1 = $this->getProduct(1);
        $product2 = $this->getProduct(2);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                new ReindexationRequestEvent(
                    [Product::class],
                    [self::WEBSITE_ID, self::WEBSITE_ID + 1, self::ANOTHER_WEBSITE_ID],
                    [$product1->getId(), $product2->getId()],
                    true,
                    ['order']
                ),
                ReindexationRequestEvent::EVENT_NAME
            );

        $event = $this->getPreUpdateEvent(['parentProduct' => [$product1, $product2]]);
        $this->listener->reindexProductOnLineItemUpdate($this->lineItem, $event);
    }

    public function testReindexProductOnLineItemCreateOrDelete(): void
    {
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('previously_purchased_products', $this->website)
            ->willReturn(true);

        $product = $this->getProduct(1);

        $this->lineItem->expects(self::once())
            ->method('getProduct')
            ->willReturn($product);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                new ReindexationRequestEvent(
                    [Product::class],
                    [self::WEBSITE_ID, self::WEBSITE_ID + 1, self::ANOTHER_WEBSITE_ID],
                    [$product->getId()],
                    true,
                    ['order']
                ),
                ReindexationRequestEvent::EVENT_NAME
            );

        $this->listener->reindexProductOnLineItemCreateOrDelete($this->lineItem);
    }

    public function testReindexProductOnLineItemCreateOrDeleteWithParentProduct(): void
    {
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('previously_purchased_products', $this->website)
            ->willReturn(true);

        $product = $this->getProduct(1);
        $parentProduct = $this->getProduct(2);

        $this->lineItem->expects(self::once())
            ->method('getProduct')
            ->willReturn($product);
        $this->lineItem->expects(self::once())
            ->method('getParentProduct')
            ->willReturn($parentProduct);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                new ReindexationRequestEvent(
                    [Product::class],
                    [self::WEBSITE_ID, self::WEBSITE_ID + 1, self::ANOTHER_WEBSITE_ID],
                    [$product->getId(), $parentProduct->getId()],
                    true,
                    ['order']
                ),
                ReindexationRequestEvent::EVENT_NAME
            );

        $this->listener->reindexProductOnLineItemCreateOrDelete($this->lineItem);
    }

    public function testReindexInOrderWithUnavailableStatus(): void
    {
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('previously_purchased_products', $this->website)
            ->willReturn(true);

        $this->order->setInternalStatus(
            $this->getInternalStatus(OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED)
        );

        $this->eventDispatcher->expects(self::never())
            ->method('dispatch');

        $event = $this->getPreUpdateEvent(['product' => [$this->getProduct(1), $this->getProduct(2)]]);
        $this->listener->reindexProductOnLineItemUpdate($this->lineItem, $event);
    }

    public function testReindexInOrderWithInvalidWebsite(): void
    {
        $this->order->unsetWebsite();

        $this->eventDispatcher->expects(self::never())
            ->method('dispatch');

        $event = $this->getPreUpdateEvent(['product' => [$this->getProduct(1), $this->getProduct(2)]]);
        $this->listener->reindexProductOnLineItemUpdate($this->lineItem, $event);
    }

    public function testFeatureDisabled(): void
    {
        $this->featureChecker->expects(self::exactly(2))
            ->method('isFeatureEnabled')
            ->with('previously_purchased_products', $this->website)
            ->willReturn(false);

        $this->eventDispatcher->expects(self::never())
            ->method('dispatch');

        $event = $this->getPreUpdateEvent(['product' => [$this->getProduct(1), $this->getProduct(2)]]);
        $this->listener->reindexProductOnLineItemUpdate($this->lineItem, $event);
        $this->listener->reindexProductOnLineItemCreateOrDelete($this->lineItem);
    }
}
