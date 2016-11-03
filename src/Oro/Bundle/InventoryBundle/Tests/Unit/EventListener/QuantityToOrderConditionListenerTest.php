<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\InventoryBundle\Tests\Unit\EventListener\Stub\CheckoutSourceStub;
use Oro\Bundle\InventoryBundle\Validator\QuantityToOrderValidatorService;
use Oro\Bundle\CheckoutBundle\Event\CheckoutValidateEvent;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Component\Action\Event\ExtendableConditionEvent;
use Oro\Bundle\InventoryBundle\EventListener\QuantityToOrderConditionListener;

use Symfony\Component\Translation\TranslatorInterface;

class QuantityToOrderConditionListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var QuantityToOrderValidatorService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $validatorService;

    /**
     * @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    /**
     * @var QuantityToOrderConditionListener
     */
    protected $quantityToOrderConditionListener;

    /**
     * @var CheckoutValidateEvent
     */
    protected $event;

    protected function setUp()
    {
        $this->validatorService = $this->getMockBuilder(QuantityToOrderValidatorService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->translator = $this->getMock(TranslatorInterface::class);
        $this->quantityToOrderConditionListener = new QuantityToOrderConditionListener(
            $this->validatorService,
            $this->translator
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

        $workflowItem->setWorkflowName('b2b_flow_checkout');
        $this->assertCheckoutValidateIgnored();

        $workflowItem->setEntity(new Checkout());
        $this->assertCheckoutValidateIgnored();
    }

    public function testOnCheckoutValidateIgnored2()
    {
        $workflowItem = new WorkflowItem();
        $workflowItem->setWorkflowName('b2b_flow_checkout');
        $checkout = new Checkout();
        $checkout->setSource(new CheckoutSource());
        $workflowItem->setEntity($checkout);
        $this->event->setContext($workflowItem);
        $this->assertCheckoutValidateIgnored();
    }

    public function testOnCheckoutValidateIgnored3()
    {
        $workflowItem = new WorkflowItem();
        $workflowItem->setWorkflowName('b2b_flow_checkout');
        $checkout = new Checkout();
        $source = new CheckoutSourceStub();
        $checkout->setSource($source);
        $source->setShoppingList(new ShoppingList());
        $source->setQuoteDemand(new QuoteDemand());

        $workflowItem->setEntity($checkout);
        $this->event->setContext($workflowItem);
        $this->assertCheckoutValidateIgnored();
    }

    public function testOnCheckoutValidateSetsRestartRequired()
    {
        $workflowItem = new WorkflowItem();
        $workflowItem->setWorkflowName('b2b_flow_checkout');
        $checkout = new Checkout();
        $source = new CheckoutSourceStub();
        $checkout->setSource($source);
        $source->setShoppingList(new ShoppingList());

        $workflowItem->setEntity($checkout);
        $this->event->setContext($workflowItem);
        $this->validatorService->expects($this->once())
            ->method('isLineItemListValid')
            ->willReturn(false);
        $this->quantityToOrderConditionListener->onCheckoutValidate($this->event);
        $this->assertTrue($this->event->isCheckoutRestartRequired());
    }

    protected function assertCheckoutValidateIgnored()
    {
        $this->validatorService->expects($this->never())
            ->method('isLineItemListValid');

        $this->quantityToOrderConditionListener->onCheckoutValidate($this->event);
    }

    public function testOnCreateOrderCheckIgnored()
    {
        $event = new ExtendableConditionEvent();
        $event->setContext(new \stdClass());

        $this->validatorService->expects($this->never())
            ->method('isLineItemListValid');
        $this->quantityToOrderConditionListener->onCreateOrderCheck($event);
    }

    public function testOnCreateOrderCheckAddsErrorToEvent()
    {
        $context = $this->getMock(ActionData::class);
        $shoppingList = $this->getMock(ShoppingList::class);
        $context->expects($this->exactly(2))
            ->method('getEntity')
            ->willReturn($shoppingList);
        $event = $this->getMock(ExtendableConditionEvent::class);
        $event->expects($this->once())
            ->method('getContext')
            ->willReturn($context);
        $event->expects($this->once())
            ->method('addError');

        $this->validatorService->expects($this->once())
            ->method('isLineItemListValid')
            ->willReturn(false);
        $this->quantityToOrderConditionListener->onCreateOrderCheck($event);
    }

    public function testOnCheckoutConditionCheckAddsError()
    {
        $workflowItem = new WorkflowItem();
        $workflowItem->setWorkflowName('b2b_flow_checkout');
        $checkout = new Checkout();
        $source = new CheckoutSourceStub();
        $checkout->setSource($source);
        $source->setShoppingList(new ShoppingList());

        $workflowItem->setEntity($checkout);

        /** @var ExtendableConditionEvent|\PHPUnit_Framework_MockObject_MockObject $event * */
        $event = $this->getMock(ExtendableConditionEvent::class);
        $event->expects($this->once())
            ->method('getContext')
            ->willReturn($workflowItem);

        $event->expects($this->once())
            ->method('addError');

        $this->quantityToOrderConditionListener->onCheckoutConditionCheck($event);
    }
}
