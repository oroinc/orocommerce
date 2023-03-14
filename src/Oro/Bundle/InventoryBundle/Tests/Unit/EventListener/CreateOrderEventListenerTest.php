<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Entity\Repository\InventoryLevelRepository;
use Oro\Bundle\InventoryBundle\EventListener\CreateOrderEventListener;
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

    /** @var InventoryStatusHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $statusHandler;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var CheckoutLineItemsManager|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutLineItemsManager;

    /** @var CreateOrderEventListener */
    private $createOrderEventListener;

    protected function setUp(): void
    {
        $this->quantityManager = $this->createMock(InventoryQuantityManager::class);
        $this->statusHandler = $this->createMock(InventoryStatusHandler::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->checkoutLineItemsManager = $this->createMock(CheckoutLineItemsManager::class);

        $this->createOrderEventListener = new CreateOrderEventListener(
            $this->quantityManager,
            $this->statusHandler,
            $this->doctrine,
            $this->checkoutLineItemsManager
        );
    }

    private function getExtendableActionEvent(): ExtendableActionEvent
    {
        $workflowItem = new WorkflowItem();
        $workflowItem->setData(new WorkflowData(['order' => $this->createMock(Order::class)]));
        $workflowItem->setEntity($this->createMock(Checkout::class));

        return new ExtendableActionEvent($workflowItem);
    }

    private function getExtendableConditionEvent(): ExtendableConditionEvent
    {
        $checkoutSource = $this->createMock(CheckoutSourceStub::class);
        $checkoutSource->expects(self::any())
            ->method('getEntity')
            ->willReturn($this->createMock(ProductLineItemsHolderInterface::class));

        $checkout = new Checkout();
        $checkout->setSource($checkoutSource);

        $workflowItem = new WorkflowItem();
        $workflowItem->setEntity($checkout);

        return new ExtendableConditionEvent($workflowItem);
    }

    private function getOrderLineItem(): OrderLineItem
    {
        $product = new Product();
        $product->setSku('TEST001');
        $productUnit = new ProductUnit();
        $productUnit->setCode('item');

        $lineItem = new OrderLineItem();
        $lineItem->setProduct($product);
        $lineItem->setProductUnit($productUnit);
        $lineItem->setQuantity(10);
        $lineItem->preSave();

        return $lineItem;
    }

    private function getLineItem(): ProductLineItemInterface
    {
        $product = new Product();
        $product->setSku('TEST001');
        $productUnit = new ProductUnit();
        $productUnit->setCode('item');

        $lineItem = $this->createMock(ProductLineItemInterface::class);
        $lineItem->expects(self::any())
            ->method('getProduct')
            ->willReturn($product);
        $lineItem->expects(self::any())
            ->method('getProductUnit')
            ->willReturn($productUnit);
        $lineItem->expects(self::any())
            ->method('getQuantity')
            ->willReturn(10);

        return $lineItem;
    }

    public function testOnCreateOrder(): void
    {
        $inventoryLevel = $this->createMock(InventoryLevel::class);

        $this->checkoutLineItemsManager->expects(self::once())
            ->method('getData')
            ->willReturn([$this->getOrderLineItem()]);

        $inventoryLevelRepository = $this->createMock(InventoryLevelRepository::class);
        $inventoryLevelRepository->expects(self::once())
            ->method('getLevelByProductAndProductUnit')
            ->willReturn($inventoryLevel);
        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(InventoryLevel::class)
            ->willReturn($inventoryLevelRepository);

        $this->quantityManager->expects(self::once())
            ->method('canDecrementInventory')
            ->willReturn(true);
        $this->quantityManager->expects(self::once())
            ->method('decrementInventory');
        $this->quantityManager->expects(self::once())
            ->method('shouldDecrement')
            ->willReturn(true);

        $this->statusHandler->expects(self::once())
            ->method('changeInventoryStatusWhenDecrement');

        $event = $this->getExtendableActionEvent();
        $this->createOrderEventListener->onCreateOrder($event);
    }

    public function testWrongContext(): void
    {
        $workflowData = $this->createMock(WorkflowData::class);
        $event = $this->createMock(ExtendableActionEvent::class);
        $event->expects(self::any())
            ->method('getContext');
        $workflowData->expects(self::never())
            ->method('get')
            ->with('order');
        $this->quantityManager->expects(self::never())
            ->method('shouldDecrement');

        $this->createOrderEventListener->onCreateOrder($event);
    }

    public function testCannotDecrement(): void
    {
        $inventoryLevel = $this->createMock(InventoryLevel::class);

        $this->checkoutLineItemsManager->expects(self::once())
            ->method('getData')
            ->willReturn([$this->getOrderLineItem()]);

        $inventoryLevelRepository = $this->createMock(InventoryLevelRepository::class);
        $inventoryLevelRepository->expects(self::once())
            ->method('getLevelByProductAndProductUnit')
            ->willReturn($inventoryLevel);
        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(InventoryLevel::class)
            ->willReturn($inventoryLevelRepository);

        $this->quantityManager->expects(self::once())
            ->method('canDecrementInventory')
            ->willReturn(false);
        $this->quantityManager->expects(self::never())
            ->method('decrementInventory');
        $this->quantityManager->expects(self::once())
            ->method('shouldDecrement')
            ->willReturn(true);

        $this->statusHandler->expects(self::never())
            ->method('changeInventoryStatusWhenDecrement');

        $event = $this->getExtendableActionEvent();
        $this->createOrderEventListener->onCreateOrder($event);
    }

    public function testNoInventoryLevel(): void
    {
        $this->checkoutLineItemsManager->expects(self::once())
            ->method('getData')
            ->willReturn([$this->getOrderLineItem()]);

        $inventoryLevelRepository = $this->createMock(InventoryLevelRepository::class);
        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(InventoryLevel::class)
            ->willReturn($inventoryLevelRepository);
        $inventoryLevelRepository->expects(self::once())
            ->method('getLevelByProductAndProductUnit')
            ->willReturn(null);

        $this->quantityManager->expects(self::once())
            ->method('shouldDecrement')
            ->willReturn(true);

        $this->quantityManager->expects(self::never())
            ->method('decrementInventory');
        $this->statusHandler->expects(self::never())
            ->method('changeInventoryStatusWhenDecrement');

        $event = $this->getExtendableActionEvent();
        $this->createOrderEventListener->onCreateOrder($event);
    }

    public function testBeforeCreateOrder(): void
    {
        $inventoryLevel = $this->createMock(InventoryLevel::class);

        $this->checkoutLineItemsManager->expects(self::once())
            ->method('getData')
            ->willReturn([$this->getLineItem()]);

        $inventoryLevelRepository = $this->createMock(InventoryLevelRepository::class);
        $inventoryLevelRepository->expects(self::once())
            ->method('getLevelByProductAndProductUnit')
            ->willReturn($inventoryLevel);
        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(InventoryLevel::class)
            ->willReturn($inventoryLevelRepository);

        $this->quantityManager->expects(self::once())
            ->method('hasEnoughQuantity')
            ->willReturn(true);
        $this->quantityManager->expects(self::once())
            ->method('shouldDecrement')
            ->willReturn(true);

        $event = $this->getExtendableConditionEvent();
        $this->createOrderEventListener->onBeforeOrderCreate($event);

        self::assertCount(0, $event->getErrors());
    }

    public function testBeforeCreateOrderLineItemError(): void
    {
        $inventoryLevel = $this->createMock(InventoryLevel::class);

        $this->checkoutLineItemsManager->expects(self::once())
            ->method('getData')
            ->willReturn([$this->getLineItem()]);

        $inventoryLevelRepository = $this->createMock(InventoryLevelRepository::class);
        $inventoryLevelRepository->expects(self::once())
            ->method('getLevelByProductAndProductUnit')
            ->willReturn($inventoryLevel);
        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(InventoryLevel::class)
            ->willReturn($inventoryLevelRepository);

        $this->quantityManager->expects(self::once())
            ->method('hasEnoughQuantity')
            ->willReturn(false);
        $this->quantityManager->expects(self::once())
            ->method('shouldDecrement')
            ->willReturn(true);

        $event = $this->getExtendableConditionEvent();
        $this->createOrderEventListener->onBeforeOrderCreate($event);

        self::assertEquals(
            [
                ['message' => '', 'context' => null]
            ],
            $event->getErrors()->toArray()
        );
    }

    public function testNoInventoryForBeforeCreate(): void
    {
        $this->checkoutLineItemsManager->expects(self::once())
            ->method('getData')
            ->willReturn([$this->getLineItem()]);

        $inventoryLevelRepository = $this->createMock(InventoryLevelRepository::class);
        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(InventoryLevel::class)
            ->willReturn($inventoryLevelRepository);
        $inventoryLevelRepository->expects(self::once())
            ->method('getLevelByProductAndProductUnit')
            ->willReturn(null);

        $this->quantityManager->expects(self::once())
            ->method('shouldDecrement')
            ->willReturn(true);

        $this->quantityManager->expects(self::never())
            ->method('decrementInventory');
        $this->statusHandler->expects(self::never())
            ->method('changeInventoryStatusWhenDecrement');

        $event = $this->getExtendableConditionEvent();
        $this->createOrderEventListener->onBeforeOrderCreate($event);
    }
}
