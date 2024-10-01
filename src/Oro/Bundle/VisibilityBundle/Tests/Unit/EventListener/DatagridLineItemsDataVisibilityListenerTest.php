<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductLineItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\VisibilityBundle\EventListener\DatagridLineItemsDataVisibilityListener;
use Oro\Bundle\VisibilityBundle\Provider\ResolvedProductVisibilityProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DatagridLineItemsDataVisibilityListenerTest extends TestCase
{
    private ResolvedProductVisibilityProvider|MockObject $resolvedProductVisibilityProvider;

    private DatagridLineItemsDataVisibilityListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->resolvedProductVisibilityProvider = $this->createMock(ResolvedProductVisibilityProvider::class);

        $this->listener = new DatagridLineItemsDataVisibilityListener($this->resolvedProductVisibilityProvider);
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
            ->method('isVisible')
            ->with($lineItem2->getProduct()->getId())
            ->willReturn(true);

        $this->listener->onLineItemData($event);

        self::assertEquals(
            [DatagridLineItemsDataVisibilityListener::IS_VISIBLE => false],
            $event->getDataForLineItem($lineItem1->getEntityIdentifier())
        );
        self::assertEquals(
            [DatagridLineItemsDataVisibilityListener::IS_VISIBLE => true],
            $event->getDataForLineItem($lineItem2->getEntityIdentifier())
        );
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
            ->method('isVisible')
            ->with($lineItem2->getProduct()->getId())
            ->willReturn(true);

        $this->listener->onLineItemData($event);

        self::assertEquals(
            [DatagridLineItemsDataVisibilityListener::IS_VISIBLE => false],
            $event->getDataForLineItem($lineItem1->getEntityIdentifier())
        );
        self::assertEquals(
            [DatagridLineItemsDataVisibilityListener::IS_VISIBLE => true],
            $event->getDataForLineItem($lineItem2->getEntityIdentifier())
        );
    }
}
