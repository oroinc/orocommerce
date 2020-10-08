<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ShoppingListBundle\Datagrid\Provider\ProductUnitsListProvider;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Event\LineItemDataBuildEvent;
use Oro\Bundle\ShoppingListBundle\EventListener\LineItemDataBuildEditListener;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class LineItemDataBuildEditListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var UrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $urlGenerator;

    /** @var ProductUnitsListProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $productUnitsListProvider;

    /** @var LineItemDataBuildEditListener */
    private $listener;

    protected function setUp(): void
    {
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->productUnitsListProvider = $this->createMock(ProductUnitsListProvider::class);
        $this->listener = new LineItemDataBuildEditListener($this->urlGenerator, $this->productUnitsListProvider);
    }

    public function testOnLineItemDataWhenNoDatagrid(): void
    {
        $event = $this->createMock(LineItemDataBuildEvent::class);
        $event
            ->expects($this->never())
            ->method('setDataForLineItem');

        $event
            ->expects($this->never())
            ->method('addDataForLineItem');

        $this->listener->onLineItemData($event);
    }

    public function testOnLineItemDataWhenNotApplicableDatagrid(): void
    {
        $event = $this->createMock(LineItemDataBuildEvent::class);
        $datagrid = $this->createMock(Datagrid::class);
        $datagrid
            ->expects($this->once())
            ->method('getName')
            ->willReturn('not-applicable-grid');
        $event
            ->expects($this->once())
            ->method('getContext')
            ->willReturn(['datagrid' => $datagrid]);

        $event
            ->expects($this->never())
            ->method('setDataForLineItem');

        $event
            ->expects($this->never())
            ->method('addDataForLineItem');

        $this->listener->onLineItemData($event);
    }

    public function testOnLineItemDataWhenNoLineItems(): void
    {
        $event = $this->createMock(LineItemDataBuildEvent::class);
        $datagrid = $this->createMock(Datagrid::class);
        $datagrid
            ->expects($this->once())
            ->method('getName')
            ->willReturn('frontend-customer-user-shopping-list-edit-grid');
        $event
            ->expects($this->once())
            ->method('getContext')
            ->willReturn(['datagrid' => $datagrid]);

        $event
            ->expects($this->once())
            ->method('getLineItems')
            ->willReturn([]);

        $event
            ->expects($this->never())
            ->method('setDataForLineItem');

        $event
            ->expects($this->never())
            ->method('addDataForLineItem');

        $this->listener->onLineItemData($event);
    }

    public function testOnLineItemData(): void
    {
        /** @var LineItem $lineItem1 */
        $lineItem1 = $this->getEntity(
            LineItem::class,
            ['id' => 11, 'product' => new Product(), 'unit' => new ProductUnit()]
        );
        /** @var LineItem $lineItem2 */
        $lineItem2 = $this->getEntity(
            LineItem::class,
            ['id' => 22, 'parentProduct' => new Product(), 'product' => new Product(), 'unit' => new ProductUnit()]
        );
        $lineItems = [$lineItem1, $lineItem2];

        $datagrid = $this->createMock(Datagrid::class);
        $datagrid
            ->expects($this->once())
            ->method('getName')
            ->willReturn('frontend-customer-user-shopping-list-edit-grid');

        $this->productUnitsListProvider
            ->expects($this->exactly(2))
            ->method('getProductUnitsList')
            ->withConsecutive(
                [$lineItem1->getProduct(), $lineItem1->getProductUnit()],
                [$lineItem2->getProduct(), $lineItem2->getProductUnit()]
            )
            ->willReturnOnConsecutiveCalls(['units_list1'], ['units_list2']);

        $this->urlGenerator
            ->expects($this->exactly(2))
            ->method('generate')
            ->withConsecutive(
                ['oro_api_shopping_list_frontend_delete_line_item', ['id' => $lineItem1->getId()]],
                [
                    'oro_api_shopping_list_frontend_delete_line_item',
                    ['id' => $lineItem2->getId(), 'onlyCurrent' => true],
                ]
            )
            ->willReturnOnConsecutiveCalls('/delete/link1', '/delete/link2');

        $event = new LineItemDataBuildEvent($lineItems, ['datagrid' => $datagrid]);

        $this->listener->onLineItemData($event);

        $this->assertEquals(
            [
                'units' => ['units_list1'],
                'deleteLink' => '/delete/link1',
            ],
            $event->getDataForLineItem($lineItem1->getId())
        );

        $this->assertEquals(
            [
                'units' => ['units_list2'],
                'deleteLink' => '/delete/link2',
            ],
            $event->getDataForLineItem($lineItem2->getId())
        );
    }
}
