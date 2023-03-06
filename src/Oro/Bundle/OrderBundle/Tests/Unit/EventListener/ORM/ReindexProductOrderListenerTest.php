<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\EventListener\ORM\ReindexProductOrderListener;
use Oro\Bundle\OrderBundle\Provider\OrderStatusesProviderInterface;
use Oro\Bundle\OrderBundle\Provider\PreviouslyPurchasedOrderStatusesProvider;
use Oro\Bundle\OrderBundle\Tests\Unit\EventListener\ORM\Stub\OrderStub;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Oro\Bundle\WebsiteSearchBundle\Provider\ReindexationWebsiteProviderInterface;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ReindexProductOrderListenerTest extends \PHPUnit\Framework\TestCase
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

    /** @var ReindexProductOrderListener */
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

        $reindexationWebsiteProvider = $this->createMock(ReindexationWebsiteProviderInterface::class);
        $reindexationWebsiteProvider->expects(self::any())
            ->method('getReindexationWebsiteIds')
            ->willReturnCallback(function (Website $website) {
                return [$website->getId(), $website->getId() + 1, self::ANOTHER_WEBSITE_ID];
            });

        $this->listener = new ReindexProductOrderListener(
            $this->eventDispatcher,
            new PreviouslyPurchasedOrderStatusesProvider(),
            $reindexationWebsiteProvider
        );
        $this->listener->setFeatureChecker($this->featureChecker);
        $this->listener->addFeature('previously_purchased_products');
    }

    private function getPreUpdateEventArgs(array $changeSet): PreUpdateEventArgs
    {
        return new PreUpdateEventArgs(
            $this->order,
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

    private function getOrderLineItem(Product $product, Product $parentProduct = null): OrderLineItem
    {
        $lineItem = new OrderLineItem();
        $lineItem->setProduct($product);
        if (null !== $parentProduct) {
            $lineItem->setParentProduct($parentProduct);
        }

        return $lineItem;
    }

    public function testOrderStatusNotChanged(): void
    {
        $this->order->setInternalStatus($this->getInternalStatus('other'));
        $this->order->setLineItems(new ArrayCollection([
            $this->getOrderLineItem($this->getProduct(1)),
            $this->getOrderLineItem($this->getProduct(2)),
            $this->getOrderLineItem($this->getProduct(3))
        ]));

        $this->featureChecker->expects(self::never())
            ->method('isFeatureEnabled')
            ->with('previously_purchased_products', $this->website)
            ->willReturn(true);

        $this->eventDispatcher->expects(self::never())
            ->method('dispatch');

        $event = $this->getPreUpdateEventArgs([]);
        $this->listener->processOrderUpdate($this->order, $event);
    }

    /**
     * @dataProvider orderStatusChangedDataProvider
     */
    public function testOrderStatusChanged(
        ?string $oldStatusId,
        ?string $newStatusId,
        bool $reindexExpected
    ): void {
        $this->order->setWebsite($this->website);
        $this->order->setLineItems(new ArrayCollection([
            $this->getOrderLineItem($this->getProduct(1)),
            $this->getOrderLineItem($this->getProduct(2)),
            $this->getOrderLineItem($this->getProduct(3))
        ]));

        $this->featureChecker->expects(self::any())
            ->method('isFeatureEnabled')
            ->with('previously_purchased_products', $this->website)
            ->willReturn(true);

        $this->eventDispatcher->expects($reindexExpected ? self::once() : self::never())
            ->method('dispatch');

        $event = $this->getPreUpdateEventArgs([
            'internal_status' => [$this->getInternalStatus($oldStatusId), $this->getInternalStatus($newStatusId)]
        ]);
        $this->listener->processOrderUpdate($this->order, $event);
    }

    public function orderStatusChangedDataProvider(): array
    {
        return [
            'Test that order status changed from available to unavailable status'   => [
                'oldStatusId'     => OrderStatusesProviderInterface::INTERNAL_STATUS_SHIPPED,
                'newStatusId'     => OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED,
                'reindexExpected' => true
            ],
            'Test that order status changed from unavailable to available status'   => [
                'oldStatusId'     => null,
                'newStatusId'     => OrderStatusesProviderInterface::INTERNAL_STATUS_SHIPPED,
                'reindexExpected' => true
            ],
            'Test that order status changed from available to available status'     => [
                'oldStatusId'     => OrderStatusesProviderInterface::INTERNAL_STATUS_SHIPPED,
                'newStatusId'     => OrderStatusesProviderInterface::INTERNAL_STATUS_CLOSED,
                'reindexExpected' => false
            ],
            'Test that order status changed from unavailable to unavailable status' => [
                'oldStatusId'     => 'some unavailable status',
                'newStatusId'     => OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED,
                'reindexExpected' => false
            ],
        ];
    }

    public function testOrderStatusChangedButOrderDoesNotHaveWebsite(): void
    {
        $this->order->setLineItems(new ArrayCollection([
            $this->getOrderLineItem($this->getProduct(1)),
            $this->getOrderLineItem($this->getProduct(2)),
            $this->getOrderLineItem($this->getProduct(3))
        ]));

        $this->featureChecker->expects(self::any())
            ->method('isFeatureEnabled')
            ->with('previously_purchased_products', $this->website)
            ->willReturn(true);

        $this->eventDispatcher->expects(self::never())
            ->method('dispatch');

        $event = $this->getPreUpdateEventArgs([
            'internal_status' => [
                $this->getInternalStatus(OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN),
                $this->getInternalStatus(OrderStatusesProviderInterface::INTERNAL_STATUS_CLOSED)
            ]
        ]);
        $this->listener->processOrderUpdate($this->order, $event);
    }

    public function testOrderStatusChangedButFeatureDisabled(): void
    {
        $this->order->setWebsite($this->website);
        $this->order->setLineItems(new ArrayCollection([
            $this->getOrderLineItem($this->getProduct(1)),
            $this->getOrderLineItem($this->getProduct(2)),
            $this->getOrderLineItem($this->getProduct(3))
        ]));

        $this->featureChecker->expects(self::any())
            ->method('isFeatureEnabled')
            ->with('previously_purchased_products', $this->website)
            ->willReturn(false);

        $this->eventDispatcher->expects(self::never())
            ->method('dispatch');

        $event = $this->getPreUpdateEventArgs([
            'internal_status' => [
                $this->getInternalStatus(OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN),
                $this->getInternalStatus(OrderStatusesProviderInterface::INTERNAL_STATUS_CLOSED)
            ]
        ]);
        $this->listener->processOrderUpdate($this->order, $event);
    }

    public function testReindexOnOrderWebsiteChange(): void
    {
        $this->order->setWebsite($this->website);
        $this->order->setLineItems(new ArrayCollection([
            $this->getOrderLineItem($this->getProduct(1)),
            $this->getOrderLineItem($this->getProduct(2)),
            $this->getOrderLineItem($this->getProduct(3))
        ]));

        $oldWebsite = $this->getWebsite(15);

        $this->featureChecker->expects(self::exactly(2))
            ->method('isFeatureEnabled')
            ->withConsecutive(
                ['previously_purchased_products', self::identicalTo($this->website)],
                ['previously_purchased_products', self::identicalTo($oldWebsite)]
            )
            ->willReturn(true);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                new ReindexationRequestEvent(
                    [Product::class],
                    [
                        self::WEBSITE_ID,
                        self::WEBSITE_ID + 1,
                        self::ANOTHER_WEBSITE_ID,
                        $oldWebsite->getId(),
                        $oldWebsite->getId() + 1
                    ],
                    [1, 2, 3],
                    true,
                    ['order']
                ),
                ReindexationRequestEvent::EVENT_NAME
            );

        $event = $this->getPreUpdateEventArgs(['website' => [$oldWebsite, $this->website]]);
        $this->listener->processOrderUpdate($this->order, $event);
    }

    /**
     * @dataProvider baseBehaviourDataProvider
     */
    public function testReindexOnOrderCustomerUserChange(
        bool $isFieldChanged,
        bool $isWebsiteSet,
        bool $isFeatureEnabled,
        string $orderStatus,
        bool $reindexExpected
    ): void {
        $changeSet = [];
        if ($isFieldChanged) {
            $changeSet['customerUser'] = [
                $this->createMock(CustomerUser::class),
                $this->createMock(CustomerUser::class)
            ];
        }
        $this->expectsBaseBehaviour($isWebsiteSet, $isFeatureEnabled, $orderStatus, $reindexExpected);

        $event = $this->getPreUpdateEventArgs($changeSet);
        $this->listener->processOrderUpdate($this->order, $event);
    }

    /**
     * @dataProvider baseBehaviourDataProvider
     */
    public function testReindexOnOrderCreatedAtChange(
        bool $isFieldChanged,
        bool $isWebsiteSet,
        bool $isFeatureEnabled,
        string $orderStatus,
        bool $reindexExpected
    ): void {
        $changeSet = [];
        if ($isFieldChanged) {
            $changeSet['createdAt'] = [new \DateTime(), new \DateTime()];
        }

        $this->expectsBaseBehaviour($isWebsiteSet, $isFeatureEnabled, $orderStatus, $reindexExpected);

        $event = $this->getPreUpdateEventArgs($changeSet);
        $this->listener->processOrderUpdate($this->order, $event);
    }

    private function expectsBaseBehaviour(
        bool $isWebsiteSet,
        bool $isFeatureEnabled,
        string $orderStatus,
        bool $reindexExpected
    ): void {
        $this->order->setInternalStatus($this->getInternalStatus($orderStatus));
        $this->order->setLineItems(new ArrayCollection([
            $this->getOrderLineItem($this->getProduct(1)),
            $this->getOrderLineItem($this->getProduct(2)),
            $this->getOrderLineItem($this->getProduct(3))
        ]));

        if ($isWebsiteSet) {
            $this->order->setWebsite($this->website);

            $this->featureChecker->expects(self::any())
                ->method('isFeatureEnabled')
                ->with('previously_purchased_products', $this->website)
                ->willReturn($isFeatureEnabled);
        } else {
            $this->featureChecker->expects(self::never())
                ->method('isFeatureEnabled');
        }

        if ($reindexExpected) {
            $this->eventDispatcher->expects(self::once())
                ->method('dispatch')
                ->with(
                    new ReindexationRequestEvent(
                        [Product::class],
                        [self::WEBSITE_ID, self::WEBSITE_ID + 1, self::ANOTHER_WEBSITE_ID],
                        [1, 2, 3],
                        true,
                        ['order']
                    ),
                    ReindexationRequestEvent::EVENT_NAME
                );
        } else {
            $this->eventDispatcher->expects(self::never())
                ->method('dispatch');
        }
    }

    public function baseBehaviourDataProvider(): array
    {
        return [
            'Field not change'                               => [
                'isFieldChanged'   => false,
                'isWebsiteSet'     => true,
                'isFeatureEnabled' => true,
                'orderStatus'      => OrderStatusesProviderInterface::INTERNAL_STATUS_CLOSED,
                'reindexExpected'  => false
            ],
            'Field changed'                                  => [
                'isFieldChanged'   => true,
                'isWebsiteSet'     => true,
                'isFeatureEnabled' => true,
                'orderStatus'      => OrderStatusesProviderInterface::INTERNAL_STATUS_CLOSED,
                'reindexExpected'  => true
            ],
            'Field changed, but website invalid'             => [
                'isFieldChanged'   => true,
                'isWebsiteSet'     => false,
                'isFeatureEnabled' => true,
                'orderStatus'      => OrderStatusesProviderInterface::INTERNAL_STATUS_CLOSED,
                'reindexExpected'  => false
            ],
            'Field changed, but feature disabled'            => [
                'isFieldChanged'   => true,
                'isWebsiteSet'     => true,
                'isFeatureEnabled' => false,
                'orderStatus'      => OrderStatusesProviderInterface::INTERNAL_STATUS_CLOSED,
                'reindexExpected'  => false
            ],
            'Field changed, but order in not allowed status' => [
                'isFieldChanged'   => true,
                'isWebsiteSet'     => true,
                'isFeatureEnabled' => true,
                'orderStatus'      => OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED,
                'reindexExpected'  => false
            ]
        ];
    }

    public function testOrderRemoved(): void
    {
        $this->order->setWebsite($this->website);
        $this->order->setLineItems(new ArrayCollection([
            $this->getOrderLineItem($this->getProduct(1)),
            $this->getOrderLineItem($this->getProduct(2)),
            $this->getOrderLineItem($this->getProduct(3)),
            $this->getOrderLineItem($this->getProduct(4), $this->getProduct(7)),
            $this->getOrderLineItem($this->getProduct(5), $this->getProduct(9))
        ]));

        $this->featureChecker->expects(self::any())
            ->method('isFeatureEnabled')
            ->with('previously_purchased_products', $this->website)
            ->willReturn(true);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                new ReindexationRequestEvent(
                    [Product::class],
                    [self::WEBSITE_ID, self::WEBSITE_ID + 1, self::ANOTHER_WEBSITE_ID],
                    [1, 2, 3, 4, 5, 7, 9],
                    true,
                    ['order']
                ),
                ReindexationRequestEvent::EVENT_NAME
            );

        $this->listener->processOrderRemove($this->order);
    }

    public function testOrderRemovedButOrderDoesNotHaveWebsite(): void
    {
        $this->order->setLineItems(new ArrayCollection([
            $this->getOrderLineItem($this->getProduct(1)),
            $this->getOrderLineItem($this->getProduct(2)),
            $this->getOrderLineItem($this->getProduct(3))
        ]));

        $this->featureChecker->expects(self::any())
            ->method('isFeatureEnabled')
            ->with('previously_purchased_products')
            ->willReturn(true);

        $this->eventDispatcher->expects(self::never())
            ->method('dispatch');

        $this->listener->processOrderRemove($this->order);
    }

    public function testReindexWhenFeatureDisabled(): void
    {
        $this->order->setWebsite($this->website);
        $this->order->setLineItems(new ArrayCollection([
            $this->getOrderLineItem($this->getProduct(1))
        ]));

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('previously_purchased_products', $this->website)
            ->willReturn(false);

        $this->eventDispatcher->expects(self::never())
            ->method('dispatch');

        $this->order->setWebsite($this->website);
        $this->listener->processOrderRemove($this->order);
    }
}
