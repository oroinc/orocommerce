<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\EventListener\DatagridLineItemsDataViolationsListener;
use Oro\Bundle\CheckoutBundle\Helper\CheckoutWorkflowHelper;
use Oro\Bundle\CheckoutBundle\Tests\Unit\Stub\CheckoutStub;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use Oro\Bundle\ShoppingListBundle\Validator\LineItemViolationsProvider;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Validator\ConstraintViolation;

class DatagridLineItemsDataViolationsListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var LineItemViolationsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $violationsProvider;

    /** @var CheckoutWorkflowHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutWorkflowHelper;

    /** @var DatagridLineItemsDataViolationsListener */
    private $listener;

    protected function setUp(): void
    {
        $this->violationsProvider = $this->createMock(LineItemViolationsProvider::class);
        $this->checkoutWorkflowHelper = $this->createMock(CheckoutWorkflowHelper::class);

        $this->listener = new DatagridLineItemsDataViolationsListener(
            $this->violationsProvider,
            $this->checkoutWorkflowHelper
        );
    }

    public function testOnLineItemDataWhenNoLineItems(): void
    {
        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid
            ->expects($this->any())
            ->method('getParameters')
            ->willReturn(new ParameterBag());

        $event = $this->createMock(DatagridLineItemsDataEvent::class);
        $event
            ->expects($this->once())
            ->method('getDatagrid')
            ->willReturn($datagrid);

        $event
            ->expects($this->never())
            ->method('addDataForLineItem');
        $event
            ->expects($this->never())
            ->method('addDataForLineItem');

        $this->listener->onLineItemData($event);
    }

    public function testOnLineItemDataWhenNoViolations(): void
    {
        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid
            ->expects($this->any())
            ->method('getParameters')
            ->willReturn(new ParameterBag());

        $event = $this->createMock(DatagridLineItemsDataEvent::class);
        $event
            ->expects($this->once())
            ->method('getDatagrid')
            ->willReturn($datagrid);

        $lineItems = [new CheckoutLineItem(), new CheckoutLineItem()];
        $event
            ->expects($this->once())
            ->method('getLineItems')
            ->willReturn($lineItems);

        $this->violationsProvider
            ->expects($this->once())
            ->method('getLineItemViolationLists')
            ->willReturn([]);

        $event
            ->expects($this->never())
            ->method('addDataForLineItem');

        $event
            ->expects($this->never())
            ->method('addDataForLineItem');

        $this->listener->onLineItemData($event);
    }

    public function testOnLineItemDataWhenViolations(): void
    {
        $productUnit = (new ProductUnit())->setCode('item');
        $product1 = (new Product())->setSku('sku1');
        $product2 = (new Product())->setSku('sku2');

        $lineItem1 = $this->getEntity(
            CheckoutLineItem::class,
            [
                'id' => 11,
                'product' => $product1,
                'productSku' => 'sku1',
                'productUnit' => $productUnit,
                'productUnitCode' => 'item'
            ]
        );
        $lineItem2 = $this->getEntity(
            CheckoutLineItem::class,
            [
                'id' => 22,
                'product' => $product2,
                'productSku' => 'sku2',
                'productUnit' => $productUnit,
                'productUnitCode' => 'item'
            ]
        );

        $checkout = new CheckoutStub();
        $checkout->setId(42);

        $workflowItem = new WorkflowItem();

        $this->checkoutWorkflowHelper->expects($this->once())
            ->method('getWorkflowItem')
            ->with($checkout)
            ->willReturn($workflowItem);

        $lineItems = [
            $lineItem1->getEntityIdentifier() => $lineItem1,
            $lineItem2->getEntityIdentifier() => $lineItem2,
        ];
        $event = new DatagridLineItemsDataEvent(
            $lineItems,
            [],
            $this->getDatagridMock($checkout),
            []
        );

        $violation1 = $this->createMock(ConstraintViolation::class);
        $violation1->expects($this->once())
            ->method('getCause')
            ->willReturn('warning');
        $violation1->expects($this->once())
            ->method('getMessage')
            ->willReturn('warning_message1');

        $violation2 = $this->createMock(ConstraintViolation::class);
        $violation2->expects($this->once())
            ->method('getCause')
            ->willReturn('warning');
        $violation2->expects($this->once())
            ->method('getMessage')
            ->willReturn('warning_message2');

        $violation3 = $this->createMock(ConstraintViolation::class);
        $violation3->expects($this->once())
            ->method('getCause')
            ->willReturn('error');
        $violation3->expects($this->once())
            ->method('getMessage')
            ->willReturn('error_message3');

        $this->violationsProvider
            ->expects($this->once())
            ->method('getLineItemViolationLists')
            ->with($lineItems, $workflowItem)
            ->willReturn(['product.sku1.item' => [$violation1], 'product.sku2.item' => [$violation2, $violation3]]);

        $this->listener->onLineItemData($event);

        $this->assertEquals(['warnings' => ['warning_message1'], 'errors' => []], $event->getDataForLineItem(11));
        $this->assertEquals(
            ['warnings' => ['warning_message2'], 'errors' => ['error_message3']],
            $event->getDataForLineItem(22)
        );
    }

    private function getDatagridMock(Checkout $checkout): DatagridInterface
    {
        $repository = $this->createMock(ObjectRepository::class);
        $repository
            ->expects($this->any())
            ->method('find')
            ->with($checkout->getId())
            ->willReturn($checkout);

        $em = $this->createMock(EntityManager::class);
        $em
            ->expects($this->any())
            ->method('getRepository')
            ->willReturn($repository);

        $qb = $this->createMock(QueryBuilder::class);
        $qb
            ->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($em);

        $datasource = $this->createMock(OrmDatasource::class);
        $datasource
            ->expects($this->any())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $datagrid = $this->createMock(DatagridInterface::class);
        $datagrid
            ->expects($this->any())
            ->method('getParameters')
            ->willReturn(new ParameterBag(['checkout_id' => $checkout->getId()]));
        $datagrid
            ->expects($this->any())
            ->method('getDatasource')
            ->willReturn($datasource);

        return $datagrid;
    }
}
