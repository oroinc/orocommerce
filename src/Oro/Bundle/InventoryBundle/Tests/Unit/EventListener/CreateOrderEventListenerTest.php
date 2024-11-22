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
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Component\Action\Event\ExtendableActionEvent;
use Oro\Component\Action\Event\ExtendableEventData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreateOrderEventListenerTest extends TestCase
{
    /** @var InventoryQuantityManager|MockObject */
    private $quantityManager;

    /** @var InventoryStatusHandler|MockObject */
    private $statusHandler;

    /** @var ManagerRegistry|MockObject */
    private $doctrine;

    /** @var CheckoutLineItemsManager|MockObject */
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
        $context = new ExtendableEventData(['order' => new Order(), 'checkout' => new Checkout()]);

        return new ExtendableActionEvent($context);
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

    public function testOnCreateOrderWithFailedPurshace(): void
    {
        $this->checkoutLineItemsManager
            ->expects(self::never())
            ->method('getData');
        $this->quantityManager
            ->expects(self::never())
            ->method('canDecrementInventory');
        $this->quantityManager
            ->expects(self::never())
            ->method('decrementInventory');
        $this->quantityManager
            ->expects(self::never())
            ->method('shouldDecrement');
        $this->statusHandler
            ->expects(self::never())
            ->method('changeInventoryStatusWhenDecrement');

        $event = new ExtendableActionEvent();
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
}
