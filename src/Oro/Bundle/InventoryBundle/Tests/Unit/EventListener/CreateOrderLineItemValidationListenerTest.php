<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener;

use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Entity\Repository\InventoryLevelRepository;
use Oro\Bundle\InventoryBundle\EventListener\CreateOrderLineItemValidationListener;
use Oro\Bundle\InventoryBundle\Exception\InventoryLevelNotFoundException;
use Oro\Bundle\InventoryBundle\Inventory\InventoryQuantityManager;
use Oro\Bundle\InventoryBundle\Tests\Unit\EventListener\Stub\CheckoutSourceStub;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ShoppingListBundle\Event\LineItemValidateEvent;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;

use Oro\Component\Checkout\LineItem\CheckoutLineItemInterface;
use Oro\Component\Checkout\LineItem\CheckoutLineItemsHolderInterface;

class CreateOrderLineItemValidationListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var InventoryQuantityManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $inventoryQuantityManager;

    /**
     * @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var CreateOrderLineItemValidationListener|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $createOrderLineItemValidationListener;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->translator = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->inventoryQuantityManager = $this->getMockBuilder(InventoryQuantityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->createOrderLineItemValidationListener = new CreateOrderLineItemValidationListener(
            $this->inventoryQuantityManager,
            $this->doctrineHelper,
            $this->translator
        );
    }

    public function testOnLineItemValidate()
    {
        $event = $this->prepareEvent();

        $inventoryLevel = $this->createMock(InventoryLevel::class);
        $inventoryLevelRepository = $this->getMockBuilder(InventoryLevelRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $inventoryLevelRepository->expects($this->once())
            ->method('getLevelByProductAndProductUnit')
            ->willReturn($inventoryLevel);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(InventoryLevel::class)
            ->willReturn($inventoryLevelRepository);
        $this->inventoryQuantityManager->expects($this->once())
            ->method('hasEnoughQuantity')
            ->willReturn(true);
        $event->expects($this->never())
            ->method('addError');
        $this->inventoryQuantityManager->expects($this->once())
            ->method('shouldDecrement')
            ->willReturn(true);

        $this->createOrderLineItemValidationListener->onLineItemValidate($event);
    }

    public function testWrongContext()
    {
        /** @var LineItemValidateEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(LineItemValidateEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('getContext')
            ->willReturn(null);
        $event->expects($this->never())
            ->method('addError');
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityRepository');
        $this->inventoryQuantityManager->expects($this->never())
            ->method('shouldDecrement');

        $this->createOrderLineItemValidationListener->onLineItemValidate($event);
    }

    public function testNoInventoryForBeforeCreate()
    {
        $this->expectException(InventoryLevelNotFoundException::class);

        $event = $this->prepareEvent();
        $inventoryLevelRepository = $this->getMockBuilder(InventoryLevelRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(InventoryLevel::class)
            ->willReturn($inventoryLevelRepository);
        $inventoryLevelRepository->expects($this->once())
            ->method('getLevelByProductAndProductUnit')
            ->willReturn(null);
        $this->inventoryQuantityManager->expects($this->once())
            ->method('shouldDecrement')
            ->willReturn(true);

        $this->createOrderLineItemValidationListener->onLineItemValidate($event);
    }

    protected function prepareEvent()
    {
        $event = $this->getMockBuilder(LineItemValidateEvent::class)->disableOriginalConstructor()->getMock();

        $numberOfItems = 5;
        $product = $this->createMock(Product::class);
        $productUnit = $this->createMock(ProductUnit::class);

        $lineItem = $this->createMock(CheckoutLineItemInterface::class);
        $lineItem->expects($this->any())->method('getProduct')->willReturn($product);
        $lineItem->expects($this->once())->method('getProductUnit')->willReturn($productUnit);
        $lineItem->expects($this->any())->method('getQuantity')->willReturn($numberOfItems);

        $checkoutLineItemsHolder = $this->createMock(CheckoutLineItemsHolderInterface::class);
        $checkoutLineItemsHolder->expects($this->once())->method('getLineItems')->willReturn([$lineItem]);

        $checkoutSource = $this->createMock(CheckoutSourceStub::class);
        $checkoutSource->expects($this->any())->method('getEntity')->willReturn($checkoutLineItemsHolder);

        $checkout = $this->createMock(Checkout::class);
        $checkout->expects($this->any())->method('getSource')->willReturn($checkoutSource);

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())->method('getEntity')->willReturn($checkout);

        $workflowStep = $this->createMock(WorkflowStep::class);
        $workflowStep->expects($this->once())->method('getName')->willReturn('order_review');

        $workflowItem->expects($this->once())->method('getCurrentStep')->willReturn($workflowStep);

        $event->expects($this->any())->method('getContext')->willReturn($workflowItem);

        return $event;
    }
}
