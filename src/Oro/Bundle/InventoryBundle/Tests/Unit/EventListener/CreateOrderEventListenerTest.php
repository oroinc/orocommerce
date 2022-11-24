<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener;

use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Entity\Repository\InventoryLevelRepository;
use Oro\Bundle\InventoryBundle\EventListener\CreateOrderEventListener;
use Oro\Bundle\InventoryBundle\Exception\InventoryLevelNotFoundException;
use Oro\Bundle\InventoryBundle\Inventory\InventoryQuantityManager;
use Oro\Bundle\InventoryBundle\Inventory\InventoryStatusHandler;
use Oro\Bundle\InventoryBundle\Tests\Unit\EventListener\Stub\CheckoutSourceStub;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Component\Action\Event\ExtendableActionEvent;
use Oro\Component\Action\Event\ExtendableConditionEvent;

class CreateOrderEventListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var InventoryQuantityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $quantityManager;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var InventoryStatusHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $statusHandler;

    /** @var EntityFallbackResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $entityFallbackResolver;

    /** @var CheckoutLineItemsManager|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutLineItemsManager;

    /** @var CreateOrderEventListener */
    private $createOrderEventListener;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->entityFallbackResolver = $this->createMock(EntityFallbackResolver::class);
        $this->statusHandler = $this->createMock(InventoryStatusHandler::class);
        $this->quantityManager = $this->createMock(InventoryQuantityManager::class);
        $this->checkoutLineItemsManager = $this->createMock(CheckoutLineItemsManager::class);

        $this->createOrderEventListener = new CreateOrderEventListener(
            $this->quantityManager,
            $this->statusHandler,
            $this->doctrineHelper,
            $this->checkoutLineItemsManager
        );
    }

    public function testOnCreateOrder()
    {
        $event = $this->prepareEvent();

        $inventoryLevel = $this->createMock(InventoryLevel::class);

        $inventoryLevelRepository = $this->createMock(InventoryLevelRepository::class);
        $inventoryLevelRepository->expects($this->once())
            ->method('getLevelByProductAndProductUnit')
            ->willReturn($inventoryLevel);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(InventoryLevel::class)
            ->willReturn($inventoryLevelRepository);

        $this->quantityManager->expects($this->once())
            ->method('canDecrementInventory')
            ->willReturn(true);
        $this->quantityManager->expects($this->once())
            ->method('decrementInventory');
        $this->quantityManager->expects($this->once())
            ->method('shouldDecrement')
            ->willReturn(true);

        $this->statusHandler->expects($this->once())
            ->method('changeInventoryStatusWhenDecrement');

        $this->createOrderEventListener->onCreateOrder($event);
    }

    public function testWrongContext()
    {
        $workflowData = $this->createMock(WorkflowData::class);
        $event = $this->createMock(ExtendableActionEvent::class);
        $event->expects($this->any())
            ->method('getContext');
        $workflowData->expects($this->never())
            ->method('get')
            ->with('order');
        $this->quantityManager->expects($this->never())
            ->method('shouldDecrement');

        $this->createOrderEventListener->onCreateOrder($event);
    }

    public function testCannotDecrement()
    {
        $event = $this->prepareEvent();

        $inventoryLevel = $this->createMock(InventoryLevel::class);
        $inventoryLevelRepository = $this->createMock(InventoryLevelRepository::class);
        $inventoryLevelRepository->expects($this->once())
            ->method('getLevelByProductAndProductUnit')
            ->willReturn($inventoryLevel);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(InventoryLevel::class)
            ->willReturn($inventoryLevelRepository);

        $this->quantityManager->expects($this->once())
            ->method('canDecrementInventory')
            ->willReturn(false);
        $this->quantityManager->expects($this->never())
            ->method('decrementInventory');
        $this->quantityManager->expects($this->once())
            ->method('shouldDecrement')
            ->willReturn(true);

        $this->statusHandler->expects($this->never())
            ->method('changeInventoryStatusWhenDecrement');

        $this->createOrderEventListener->onCreateOrder($event);
    }

    public function testNoInventoryLevel()
    {
        $this->expectException(InventoryLevelNotFoundException::class);

        $event = $this->prepareEvent();
        $inventoryLevelRepository = $this->createMock(InventoryLevelRepository::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(InventoryLevel::class)
            ->willReturn($inventoryLevelRepository);
        $inventoryLevelRepository->expects($this->once())
            ->method('getLevelByProductAndProductUnit')
            ->willReturn(null);
        $this->quantityManager->expects($this->once())
            ->method('shouldDecrement')
            ->willReturn(true);

        $this->createOrderEventListener->onCreateOrder($event);
    }

    public function testBeforeCreateOrder()
    {
        $event = $this->prepareConditionEvent();

        $inventoryLevel = $this->createMock(InventoryLevel::class);
        $inventoryLevelRepository = $this->createMock(InventoryLevelRepository::class);
        $inventoryLevelRepository->expects($this->once())
            ->method('getLevelByProductAndProductUnit')
            ->willReturn($inventoryLevel);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(InventoryLevel::class)
            ->willReturn($inventoryLevelRepository);

        $this->quantityManager->expects($this->once())
            ->method('hasEnoughQuantity')
            ->willReturn(true);
        $this->quantityManager->expects($this->once())
            ->method('shouldDecrement')
            ->willReturn(true);

        $event->expects($this->never())
            ->method('addError');

        $this->createOrderEventListener->onBeforeOrderCreate($event);
    }

    public function testBeforeCreateOrderLineItemError()
    {
        $event = $this->prepareConditionEvent();

        $inventoryLevel = $this->createMock(InventoryLevel::class);
        $inventoryLevelRepository = $this->createMock(InventoryLevelRepository::class);
        $inventoryLevelRepository->expects($this->once())
            ->method('getLevelByProductAndProductUnit')
            ->willReturn($inventoryLevel);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(InventoryLevel::class)
            ->willReturn($inventoryLevelRepository);
        $this->quantityManager->expects($this->once())
            ->method('hasEnoughQuantity')
            ->willReturn(false);
        $this->quantityManager->expects($this->once())
            ->method('shouldDecrement')
            ->willReturn(true);
        $event->expects($this->once())
            ->method('addError');

        $this->createOrderEventListener->onBeforeOrderCreate($event);
    }

    public function testNoInventoryForBeforeCreate()
    {
        $this->expectException(InventoryLevelNotFoundException::class);

        $event = $this->prepareConditionEvent();
        $inventoryLevelRepository = $this->createMock(InventoryLevelRepository::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(InventoryLevel::class)
            ->willReturn($inventoryLevelRepository);
        $inventoryLevelRepository->expects($this->once())
            ->method('getLevelByProductAndProductUnit')
            ->willReturn(null);
        $this->quantityManager->expects($this->once())
            ->method('shouldDecrement')
            ->willReturn(true);

        $this->createOrderEventListener->onBeforeOrderCreate($event);
    }

    private function prepareEvent(): ExtendableActionEvent
    {
        $numberOfItems = 5;
        $event = $this->createMock(ExtendableActionEvent::class);
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowData = $this->createMock(WorkflowData::class);
        $order = $this->createMock(Order::class);
        $product = $this->createMock(Product::class);
        $productUnit = $this->createMock(ProductUnit::class);
        $lineItem = $this->createMock(OrderLineItem::class);
        $checkout = $this->createMock(Checkout::class);
        $lineItem->expects($this->any())
            ->method('getProduct')
            ->willReturn($product);
        $lineItem->expects($this->once())
            ->method('getProductUnit')
            ->willReturn($productUnit);
        $lineItem->expects($this->any())
            ->method('getQuantity')
            ->willReturn($numberOfItems);
        $this->checkoutLineItemsManager->expects($this->once())
            ->method('getData')
            ->willReturn([$lineItem]);
        $workflowData->expects($this->once())
            ->method('has')
            ->with('order')
            ->willReturn(true);
        $workflowData->expects($this->any())
            ->method('get')
            ->with('order')
            ->willReturn($order);
        $workflowItem->expects($this->any())
            ->method('getData')
            ->willReturn($workflowData);
        $workflowItem->expects($this->any())
            ->method('getEntity')
            ->willReturn($checkout);
        $event->expects($this->any())
            ->method('getContext')
            ->willReturn($workflowItem);

        return $event;
    }

    private function prepareConditionEvent(): ExtendableConditionEvent|\PHPUnit\Framework\MockObject\MockObject
    {
        $numberOfItems = 5;
        $event = $this->createMock(ExtendableConditionEvent::class);
        $workflowItem = $this->createMock(WorkflowItem::class);
        $checkout = $this->createMock(Checkout::class);
        $checkoutSource = $this->createMock(CheckoutSourceStub::class);
        $checkoutLineItemsHolder = $this->createMock(ProductLineItemsHolderInterface::class);
        $product = $this->createMock(Product::class);
        $productUnit = $this->createMock(ProductUnit::class);
        $lineItem = $this->createMock(ProductLineItemInterface::class);
        $lineItem->expects($this->any())
            ->method('getProduct')
            ->willReturn($product);
        $lineItem->expects($this->once())
            ->method('getProductUnit')
            ->willReturn($productUnit);
        $lineItem->expects($this->any())
            ->method('getQuantity')
            ->willReturn($numberOfItems);
        $checkout->expects($this->any())
            ->method('getSource')
            ->willReturn($checkoutSource);
        $checkoutSource->expects($this->any())
            ->method('getEntity')
            ->willReturn($checkoutLineItemsHolder);
        $this->checkoutLineItemsManager->expects($this->once())
            ->method('getData')
            ->willReturn([$lineItem]);
        $workflowItem->expects($this->any())
            ->method('getEntity')
            ->willReturn($checkout);
        $event->expects($this->any())
            ->method('getContext')
            ->willReturn($workflowItem);

        return $event;
    }
}
