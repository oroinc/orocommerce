<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
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
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Event\LineItemValidateEvent;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;

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
     * @var RequestStack|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestStack;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var CreateOrderLineItemValidationListener|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $createOrderLineItemValidationListener;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->translator = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestStack = $this->getMockBuilder(RequestStack::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->inventoryQuantityManager = $this->getMockBuilder(InventoryQuantityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->createOrderLineItemValidationListener = new CreateOrderLineItemValidationListener(
            $this->inventoryQuantityManager,
            $this->doctrineHelper,
            $this->translator,
            $this->requestStack
        );
    }

    public function testOnLineItemValidate()
    {
        $event = $this->prepareEvent();

        $itemsInStock = 10;
        $inventoryLevel = $this->createMock(InventoryLevel::class);
        $inventoryLevelRepository = $this->getMockBuilder(InventoryLevelRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $inventoryLevel->expects($this->any())
            ->method('getQuantity')
            ->willReturn($itemsInStock);
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

        $this->createOrderLineItemValidationListener->onLineItemValidate($event);
    }

    public function testWrongContext()
    {
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

        $this->createOrderLineItemValidationListener->onLineItemValidate($event);
    }

    protected function prepareEvent()
    {
        $numberOfItems = 5;
        $event = $this->getMockBuilder(LineItemValidateEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowStep = $this->createMock(WorkflowStep::class);
        $checkout = $this->createMock(Checkout::class);
        $checkoutSource = $this->createMock(CheckoutSourceStub::class);
        $shoppingList = $this->createMock(ShoppingList::class);
        $product = $this->createMock(Product::class);
        $productUnit = $this->createMock(ProductUnit::class);
        $lineItem = $this->createMock(LineItem::class);
        $request = $this->createMock(Request::class);
        $request->expects($this->once())
            ->method('isMethod')
            ->with('POST')
            ->willReturn(true);
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);
        $lineItem->expects($this->once())
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
            ->method('getShoppingList')
            ->willReturn($shoppingList);
        $shoppingList->expects($this->once())
            ->method('getLineItems')
            ->willReturn([$lineItem]);
        $workflowItem->expects($this->any())
            ->method('getEntity')
            ->willReturn($checkout);
        $workflowStep->expects($this->once())
            ->method('getName')
            ->willReturn('order_review');
        $workflowItem->expects($this->once())
            ->method('getCurrentStep')
            ->willReturn($workflowStep);
        $event->expects($this->any())
            ->method('getContext')
            ->willReturn($workflowItem);

        return $event;
    }
}
