<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\CheckoutBundle\Event\CheckoutValidateEvent;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\InventoryBundle\EventListener\QuantityToOrderConditionListener;
use Oro\Bundle\InventoryBundle\Tests\Unit\EventListener\Stub\CheckoutSourceStub;
use Oro\Bundle\InventoryBundle\Validator\QuantityToOrderValidatorService;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowResult;
use Oro\Component\Action\Event\ExtendableConditionEvent;
use Oro\Component\Testing\Unit\EntityTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class QuantityToOrderConditionListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var string
     */
    protected const WORKFLOW_NAME = 'b2b_flow_checkout';

    /**
     * @var QuantityToOrderValidatorService|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $validatorService;

    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $doctrineHelper;

    /**
     * @var QuantityToOrderConditionListener
     */
    protected $quantityToOrderConditionListener;

    /**
     * @var CheckoutValidateEvent
     */
    protected $event;

    protected function setUp(): void
    {
        $this->validatorService = $this->createMock(QuantityToOrderValidatorService::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->quantityToOrderConditionListener = new QuantityToOrderConditionListener(
            $this->validatorService,
            $this->doctrineHelper
        );

        $this->event = new CheckoutValidateEvent();
    }

    public function testOnCheckoutValidateIgnored1()
    {
        $this->event->setContext(null);
        $this->assertCheckoutValidateIgnored();

        $workflowItem = new WorkflowItem();
        $this->event->setContext($workflowItem);
        $this->assertCheckoutValidateIgnored();

        $workflowItem = new WorkflowItem();
        $workflowItem->setWorkflowName('noname');
        $this->assertCheckoutValidateIgnored();

        $workflowItem->setWorkflowName(static::WORKFLOW_NAME);
        $this->assertCheckoutValidateIgnored();

        $workflowItem->setEntity(new Checkout());
        $this->assertCheckoutValidateIgnored();
    }

    public function testOnCheckoutValidateIgnored2()
    {
        $workflowItem = new WorkflowItem();
        $workflowItem->setWorkflowName(static::WORKFLOW_NAME);

        $source = new CheckoutSourceStub();
        $source->setQuoteDemand(new QuoteDemand());

        $checkout = new Checkout();
        $checkout->setSource($source);

        $workflowItem->setEntity($checkout);
        $this->event->setContext($workflowItem);
        $this->assertCheckoutValidateIgnored();
    }

    public function testOnCheckoutValidateNotIgnoredWoSource()
    {
        $workflowItem = new WorkflowItem();
        $workflowItem->setWorkflowName(static::WORKFLOW_NAME);

        $checkout = new Checkout();
        $checkout->setSource(new CheckoutSource());

        $workflowItem->setEntity($checkout);

        $this->event->setContext($workflowItem);
        $this->validatorService->expects($this->exactly(2))
            ->method('isLineItemListValid')
            ->willReturn(false);
        $this->quantityToOrderConditionListener->onCheckoutValidate($this->event);
        $this->assertTrue($this->event->isCheckoutRestartRequired());

        // check local cache without source entity
        $this->quantityToOrderConditionListener->onCheckoutValidate($this->event);
    }

    public function testOnCheckoutValidateSetsRestartRequired()
    {
        $workflowItem = new WorkflowItem();
        $workflowItem->setWorkflowName(static::WORKFLOW_NAME);

        $source = new CheckoutSourceStub();
        $source->setShoppingList(new ShoppingList());

        $checkout = new Checkout();
        $checkout->setSource($source);

        $workflowItem->setEntity($checkout);
        $this->event->setContext($workflowItem);
        $this->validatorService->expects($this->once())
            ->method('isLineItemListValid')
            ->willReturn(false);
        $this->quantityToOrderConditionListener->onCheckoutValidate($this->event);
        $this->assertTrue($this->event->isCheckoutRestartRequired());

        // check local cache
        $this->quantityToOrderConditionListener->onCheckoutValidate($this->event);
    }

    protected function assertCheckoutValidateIgnored()
    {
        $this->validatorService->expects($this->never())
            ->method('isLineItemListValid');

        $this->quantityToOrderConditionListener->onCheckoutValidate($this->event);
    }

    public function testOnStartCheckoutConditionWhenContextIsNotActionData()
    {
        $event = new ExtendableConditionEvent(new WorkflowItem());

        $this->validatorService->expects($this->never())
            ->method('isLineItemListValid');
        $this->quantityToOrderConditionListener->onStartCheckoutConditionCheck($event);
    }

    public function testOnStartCheckoutConditionWhenCheckoutIsNotOfCheckoutType()
    {
        $event = new ExtendableConditionEvent(new ActionData(['checkout' => new \stdClass()]));

        $this->validatorService->expects($this->never())
            ->method('isLineItemListValid');
        $this->quantityToOrderConditionListener->onStartCheckoutConditionCheck($event);
    }

    public function testOnStartCheckoutConditionWhenSourceEntityIsNotOfQuoteDemandType()
    {
        $source = new CheckoutSourceStub();
        $source->setShoppingList(new ShoppingList());

        $checkout = new Checkout();
        $checkout->setSource($source);

        $event = new ExtendableConditionEvent(new ActionData(['checkout' => $checkout]));

        $this->validatorService->expects($this->once())
            ->method('isLineItemListValid')
            ->willReturn(true);
        $this->quantityToOrderConditionListener->onStartCheckoutConditionCheck($event);

        // check local cache
        $this->quantityToOrderConditionListener->onStartCheckoutConditionCheck($event);
    }

    public function testOnStartCheckoutConditionCheckAddsErrorToEvent()
    {
        $lineItems = new ArrayCollection([$this->getEntity(CheckoutLineItem::class)]);
        $shoppingList = $this->getEntity(ShoppingList::class);
        $checkoutSource = new CheckoutSourceStub();
        $checkoutSource->setShoppingList($shoppingList);
        $checkout = $this->getEntity(Checkout::class, ['source' => $checkoutSource, 'lineItems' => $lineItems]);
        $context = new ActionData(['checkout' => $checkout]);
        $event = new ExtendableConditionEvent($context);

        $this->validatorService
            ->expects($this->once())
            ->method('isLineItemListValid')
            ->with($lineItems)
            ->willReturn(false);

        $this->quantityToOrderConditionListener->onStartCheckoutConditionCheck($event);
        $this->assertNotEmpty($event->getErrors());

        // check local cache
        $this->quantityToOrderConditionListener->onStartCheckoutConditionCheck($event);
    }

    public function testOnStartCheckoutConditionCheckAddsNoErrorToEvent()
    {
        $lineItems = new ArrayCollection([$this->getEntity(CheckoutLineItem::class)]);
        $shoppingList = $this->getEntity(ShoppingList::class);
        $checkoutSource = new CheckoutSourceStub();
        $checkoutSource->setShoppingList($shoppingList);
        $checkout = $this->getEntity(Checkout::class, ['source' => $checkoutSource, 'lineItems' => $lineItems]);
        $context = new ActionData(['checkout' => $checkout]);
        $event = new ExtendableConditionEvent($context);

        $this->validatorService
            ->expects($this->once())
            ->method('isLineItemListValid')
            ->with($lineItems)
            ->willReturn(true);

        $this->quantityToOrderConditionListener->onStartCheckoutConditionCheck($event);
        $this->assertEmpty($event->getErrors());

        // check local cache
        $this->quantityToOrderConditionListener->onStartCheckoutConditionCheck($event);
    }

    public function testOnCheckoutConditionCheckAddsError()
    {
        $workflowItem = new WorkflowItem();
        $workflowItem->setWorkflowName(static::WORKFLOW_NAME);
        $checkout = new Checkout();
        $source = new CheckoutSourceStub();
        $checkout->setSource($source);
        $source->setShoppingList(new ShoppingList());

        $workflowItem->setEntity($checkout);

        $event = $this->createMock(ExtendableConditionEvent::class);
        $event->expects($this->any())
            ->method('getContext')
            ->willReturn($workflowItem);

        $this->validatorService->expects($this->once())
            ->method('isLineItemListValid')
            ->willReturn(false);

        $event->expects($this->exactly(2))
            ->method('addError')
            ->with(QuantityToOrderConditionListener::QUANTITY_CHECK_ERROR);

        $this->quantityToOrderConditionListener->onCheckoutConditionCheck($event);

        // check local cache
        $this->quantityToOrderConditionListener->onCheckoutConditionCheck($event);
    }

    public function testOnShoppingListStart()
    {
        $event = $this->createMock(ExtendableConditionEvent::class);
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->method('getWorkflowName')->willReturn(static::WORKFLOW_NAME);
        $workflowResult = $this->createMock(WorkflowResult::class);
        $workflowResult->method('has')->willReturn(true);
        $workflowItem->method('getResult')->willReturn($workflowResult);

        $lineItems = [$this->createMock(LineItem::class), $this->createMock(LineItem::class)];
        $shoppingList = $this->createMock(ShoppingList::class);
        $shoppingList->method('getLineItems')->willReturn($lineItems);
        $workflowResult->method('get')->willReturn($shoppingList);

        $this->validatorService->expects($this->once())->method('isLineItemListValid')->willReturn(false);
        $event->method('getContext')->willReturn($workflowItem);

        $event->expects($this->once())
            ->method('addError');
        $this->quantityToOrderConditionListener->onShoppingListStart($event);
    }
}
