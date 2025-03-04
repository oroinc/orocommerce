<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Entity\Repository\InventoryLevelRepository;
use Oro\Bundle\InventoryBundle\EventListener\CreateOrderEventListener;
use Oro\Bundle\InventoryBundle\Inventory\InventoryQuantityManager;
use Oro\Bundle\InventoryBundle\Inventory\InventoryStatusHandler;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Component\Action\Event\ExtendableActionEvent;

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

    #[\Override]
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
        return new ExtendableActionEvent(new ActionData(['checkout' => new Checkout()]));
    }

    private function getExtendableEmptyActionEvent(): ExtendableActionEvent
    {
        return new ExtendableActionEvent(new ActionData([]));
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

        $event = $this->getExtendableEmptyActionEvent(false);
        $this->createOrderEventListener->onCreateOrder($event);
    }

    public function testWrongContext(): void
    {
        $workflowData = $this->createMock(WorkflowData::class);
        $event = $this->getExtendableEmptyActionEvent();
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
