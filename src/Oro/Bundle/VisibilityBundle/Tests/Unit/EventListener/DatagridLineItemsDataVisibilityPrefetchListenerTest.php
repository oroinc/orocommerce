<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductKitItemLineItemsAwareStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductKitItemLineItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductLineItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\VisibilityBundle\EventListener\DatagridLineItemsDataVisibilityPrefetchListener;
use Oro\Bundle\VisibilityBundle\Provider\ResolvedProductVisibilityProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DatagridLineItemsDataVisibilityPrefetchListenerTest extends TestCase
{
    private ResolvedProductVisibilityProvider|MockObject $resolvedProductVisibilityProvider;

    private DatagridLineItemsDataVisibilityPrefetchListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->resolvedProductVisibilityProvider = $this->createMock(ResolvedProductVisibilityProvider::class);

        $this->listener = new DatagridLineItemsDataVisibilityPrefetchListener($this->resolvedProductVisibilityProvider);
    }

    public function testOnLineItemDataWhenNoLineItems(): void
    {
        $event = $this->createMock(DatagridLineItemsDataEvent::class);
        $event
            ->expects(self::once())
            ->method('getLineItems')
            ->willReturn([]);

        $event
            ->expects(self::never())
            ->method('addDataForLineItem');

        $this->listener->onLineItemData($event);
    }

    public function testOnLineItemDataWhenHasLineItemWithoutProduct(): void
    {
        $lineItem1 = (new ProductLineItemStub(10));
        $lineItem2 = (new ProductLineItemStub(20))
            ->setProduct((new ProductStub())->setId(201)->setStatus(Product::STATUS_ENABLED));
        $event = new DatagridLineItemsDataEvent(
            [$lineItem1->getEntityIdentifier() => $lineItem1, $lineItem2->getEntityIdentifier() => $lineItem2],
            [],
            $this->createMock(Datagrid::class),
            []
        );

        $this->resolvedProductVisibilityProvider
            ->expects(self::once())
            ->method('prefetch')
            ->with([$lineItem2->getProduct()->getId()]);

        $this->listener->onLineItemData($event);
    }

    public function testOnLineItemDataWhenHasLineItemWithDisabledProduct(): void
    {
        $lineItem1 = (new ProductLineItemStub(10))
            ->setProduct((new ProductStub())->setId(101)->setStatus(Product::STATUS_DISABLED));
        $lineItem2 = (new ProductLineItemStub(20))
            ->setProduct((new ProductStub())->setId(201)->setStatus(Product::STATUS_ENABLED));
        $event = new DatagridLineItemsDataEvent(
            [$lineItem1->getEntityIdentifier() => $lineItem1, $lineItem2->getEntityIdentifier() => $lineItem2],
            [],
            $this->createMock(Datagrid::class),
            []
        );

        $this->resolvedProductVisibilityProvider
            ->expects(self::once())
            ->method('prefetch')
            ->with([$lineItem2->getProduct()->getId()]);

        $this->listener->onLineItemData($event);
    }

    public function testOnLineItemDataWhenHasLineItemWithParentProduct(): void
    {
        $lineItem1 = (new ProductLineItemStub(10))
            ->setProduct((new ProductStub())->setId(101)->setStatus(Product::STATUS_ENABLED))
            ->setParentProduct((new ProductStub())->setId(1001)->setStatus(Product::STATUS_ENABLED));
        $lineItem2 = (new ProductLineItemStub(20))
            ->setProduct((new ProductStub())->setId(201)->setStatus(Product::STATUS_ENABLED))
            ->setParentProduct((new ProductStub())->setId(1001)->setStatus(Product::STATUS_DISABLED));

        $event = new DatagridLineItemsDataEvent(
            [$lineItem1->getEntityIdentifier() => $lineItem1, $lineItem2->getEntityIdentifier() => $lineItem2],
            [],
            $this->createMock(Datagrid::class),
            []
        );

        $this->resolvedProductVisibilityProvider
            ->expects(self::once())
            ->method('prefetch')
            ->with(
                [
                    $lineItem1->getProduct()->getId(),
                    $lineItem1->getParentProduct()->getId(),
                    $lineItem2->getProduct()->getId(),
                ]
            );

        $this->listener->onLineItemData($event);
    }

    public function testOnLineItemDataWhenHasKitItemLineItemWithoutProduct(): void
    {
        $lineItem1 = (new ProductLineItemStub(10))
            ->setProduct((new ProductStub())->setId(101)->setStatus(Product::STATUS_ENABLED));
        $kitItemLineItem1 = new ProductKitItemLineItemStub(2001);
        $kitItemLineItem2 = (new ProductKitItemLineItemStub(2002))
            ->setProduct((new ProductStub())->setId(20002)->setStatus(Product::STATUS_ENABLED));
        $lineItem2 = (new ProductKitItemLineItemsAwareStub(20))
            ->setProduct((new ProductStub())->setId(201)->setStatus(Product::STATUS_ENABLED))
            ->addKitItemLineItem($kitItemLineItem1)
            ->addKitItemLineItem($kitItemLineItem2);
        $event = new DatagridLineItemsDataEvent(
            [$lineItem1->getEntityIdentifier() => $lineItem1, $lineItem2->getEntityIdentifier() => $lineItem2],
            [$lineItem2->getEntityIdentifier() => ['type' => Product::TYPE_KIT]],
            $this->createMock(Datagrid::class),
            []
        );

        $this->resolvedProductVisibilityProvider
            ->expects(self::once())
            ->method('prefetch')
            ->with(
                [
                    $lineItem1->getProduct()->getId(),
                    $lineItem2->getProduct()->getId(),
                    $kitItemLineItem2->getProduct()->getId(),
                ]
            );

        $this->listener->onLineItemData($event);
    }

    public function testOnLineItemDataWhenHasKitItemLineItemWithDisabledProduct(): void
    {
        $lineItem1 = (new ProductLineItemStub(10))
            ->setProduct((new ProductStub())->setId(101)->setStatus(Product::STATUS_ENABLED));
        $kitItemLineItem1 = (new ProductKitItemLineItemStub(2001))
            ->setProduct((new ProductStub())->setId(20001)->setStatus(Product::STATUS_DISABLED));
        $kitItemLineItem2 = (new ProductKitItemLineItemStub(2002))
            ->setProduct((new ProductStub())->setId(20002)->setStatus(Product::STATUS_ENABLED));
        $lineItem2 = (new ProductKitItemLineItemsAwareStub(20))
            ->setProduct((new ProductStub())->setId(201)->setStatus(Product::STATUS_ENABLED))
            ->addKitItemLineItem($kitItemLineItem1)
            ->addKitItemLineItem($kitItemLineItem2);
        $event = new DatagridLineItemsDataEvent(
            [$lineItem1->getEntityIdentifier() => $lineItem1, $lineItem2->getEntityIdentifier() => $lineItem2],
            [$lineItem2->getEntityIdentifier() => ['type' => Product::TYPE_KIT]],
            $this->createMock(Datagrid::class),
            []
        );

        $this->resolvedProductVisibilityProvider
            ->expects(self::once())
            ->method('prefetch')
            ->with(
                [
                    $lineItem1->getProduct()->getId(),
                    $lineItem2->getProduct()->getId(),
                    $kitItemLineItem2->getProduct()->getId(),
                ]
            );

        $this->listener->onLineItemData($event);
    }
}
