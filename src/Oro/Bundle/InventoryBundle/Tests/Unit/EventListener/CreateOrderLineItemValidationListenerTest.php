<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Entity\Repository\InventoryLevelRepository;
use Oro\Bundle\InventoryBundle\EventListener\CreateOrderLineItemValidationListener;
use Oro\Bundle\InventoryBundle\Inventory\InventoryQuantityManager;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ShoppingListBundle\Event\LineItemValidateEvent;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Symfony\Contracts\Translation\TranslatorInterface;

class CreateOrderLineItemValidationListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var InventoryQuantityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $quantityManager;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var CheckoutLineItemsManager|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutLineItemsManager;

    /** @var CreateOrderLineItemValidationListener */
    private $createOrderLineItemValidationListener;

    protected function setUp(): void
    {
        $this->quantityManager = $this->createMock(InventoryQuantityManager::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->checkoutLineItemsManager = $this->createMock(CheckoutLineItemsManager::class);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(function ($value) {
                return $value . ' (translated)';
            });

        $this->createOrderLineItemValidationListener = new CreateOrderLineItemValidationListener(
            $this->quantityManager,
            $this->doctrine,
            $translator,
            $this->checkoutLineItemsManager
        );
    }

    private function getEvent(string $stepName): LineItemValidateEvent
    {
        $checkout = new Checkout();
        $workflowStep = new WorkflowStep();
        $workflowStep->setName($stepName);

        $workflowItem = new WorkflowItem();
        $workflowItem->setEntity($checkout);
        $workflowItem->setCurrentStep($workflowStep);

        return new LineItemValidateEvent([], $workflowItem);
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

    /**
     * @dataProvider onLineItemValidateProvider
     */
    public function testOnLineItemValidate(string $stepName): void
    {
        $inventoryLevelRepository = $this->createMock(InventoryLevelRepository::class);
        $inventoryLevelRepository->expects(self::once())
            ->method('getLevelByProductAndProductUnit')
            ->willReturn($this->createMock(InventoryLevel::class));
        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(InventoryLevel::class)
            ->willReturn($inventoryLevelRepository);

        $this->checkoutLineItemsManager->expects(self::once())
            ->method('getData')
            ->willReturn([$this->getOrderLineItem()]);

        $this->quantityManager->expects(self::once())
            ->method('shouldDecrement')
            ->willReturn(true);
        $this->quantityManager->expects(self::once())
            ->method('hasEnoughQuantity')
            ->willReturn(true);

        $event = $this->getEvent($stepName);
        $this->createOrderLineItemValidationListener->onLineItemValidate($event);

        self::assertCount(0, $event->getErrors());
    }

    /**
     * @dataProvider onLineItemValidateProvider
     */
    public function testOnLineItemValidateWithError(string $stepName): void
    {
        $inventoryLevelRepository = $this->createMock(InventoryLevelRepository::class);
        $inventoryLevelRepository->expects(self::once())
            ->method('getLevelByProductAndProductUnit')
            ->willReturn($this->createMock(InventoryLevel::class));
        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(InventoryLevel::class)
            ->willReturn($inventoryLevelRepository);

        $this->checkoutLineItemsManager->expects(self::once())
            ->method('getData')
            ->willReturn([$this->getOrderLineItem()]);

        $this->quantityManager->expects(self::once())
            ->method('shouldDecrement')
            ->willReturn(true);
        $this->quantityManager->expects(self::once())
            ->method('hasEnoughQuantity')
            ->willReturn(false);

        $event = $this->getEvent($stepName);
        $this->createOrderLineItemValidationListener->onLineItemValidate($event);

        self::assertEquals(
            [
                [
                    'sku' => 'TEST001',
                    'unit' => 'item',
                    'message' => 'oro.inventory.decrement_inventory.product.not_allowed (translated)'
                ]
            ],
            $event->getErrors()->toArray()
        );
    }

    public function onLineItemValidateProvider(): array
    {
        return [
            ['step' => 'order_review'],
            ['step' => 'checkout'],
            ['step' => 'request_approval'],
            ['step' => 'approve_request'],
        ];
    }

    public function testWrongContext(): void
    {
        $this->doctrine->expects(self::never())
            ->method('getRepository');

        $this->quantityManager->expects(self::never())
            ->method('shouldDecrement');

        $event = new LineItemValidateEvent([], null);
        $this->createOrderLineItemValidationListener->onLineItemValidate($event);

        self::assertCount(0, $event->getErrors());
    }

    public function testOnLineItemValidateWhenInventoryLevelNotFound(): void
    {
        $inventoryLevelRepository = $this->createMock(InventoryLevelRepository::class);
        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(InventoryLevel::class)
            ->willReturn($inventoryLevelRepository);
        $inventoryLevelRepository->expects(self::once())
            ->method('getLevelByProductAndProductUnit')
            ->willReturn(null);

        $this->checkoutLineItemsManager->expects(self::once())
            ->method('getData')
            ->willReturn([$this->getOrderLineItem()]);

        $this->quantityManager->expects(self::once())
            ->method('shouldDecrement')
            ->willReturn(true);

        $event = $this->getEvent('order_review');
        $this->createOrderLineItemValidationListener->onLineItemValidate($event);

        self::assertEquals(
            [
                [
                    'sku' => 'TEST001',
                    'unit' => 'item',
                    'message' => 'oro.inventory.decrement_inventory.product.not_allowed (translated)'
                ]
            ],
            $event->getErrors()->toArray()
        );
    }
}
