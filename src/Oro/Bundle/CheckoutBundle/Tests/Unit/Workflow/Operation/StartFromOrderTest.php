<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\Operation;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Factory\CheckoutLineItemsFactory;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutLineItemsProvider;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\StartCheckoutInterface;
use Oro\Bundle\CheckoutBundle\Workflow\Operation\StartFromOrder;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StartFromOrderTest extends TestCase
{
    private WorkflowManager|MockObject $workflowManager;
    private CheckoutLineItemsFactory|MockObject $lineItemsFactory;
    private StartCheckoutInterface|MockObject $startCheckout;
    private CheckoutLineItemsProvider|MockObject $checkoutLineItemsProvider;
    private ActionExecutor|MockObject $actionExecutor;
    private StartFromOrder $operation;

    protected function setUp(): void
    {
        $this->workflowManager = $this->createMock(WorkflowManager::class);
        $this->lineItemsFactory = $this->createMock(CheckoutLineItemsFactory::class);
        $this->startCheckout = $this->createMock(StartCheckoutInterface::class);
        $this->checkoutLineItemsProvider = $this->createMock(CheckoutLineItemsProvider::class);
        $this->actionExecutor = $this->createMock(ActionExecutor::class);

        $this->operation = new StartFromOrder(
            $this->workflowManager,
            $this->lineItemsFactory,
            $this->startCheckout,
            $this->checkoutLineItemsProvider,
            $this->actionExecutor
        );
    }

    public function testIsPreConditionAllowed(): void
    {
        $workflow = $this->createMock(Workflow::class);

        $this->workflowManager->expects($this->once())
            ->method('getAvailableWorkflowByRecordGroup')
            ->with(Checkout::class, 'b2b_checkout_flow')
            ->willReturn($workflow);

        $data = $this->createMock(ActionData::class);
        $this->assertTrue($this->operation->isPreConditionAllowed($data));
    }

    public function testIsPreConditionAllowedWhenNoWorkflow(): void
    {
        $this->workflowManager->expects($this->once())
            ->method('getAvailableWorkflowByRecordGroup')
            ->with(Checkout::class, 'b2b_checkout_flow')
            ->willReturn(null);

        $data = $this->createMock(ActionData::class);
        $this->assertFalse($this->operation->isPreConditionAllowed($data));
    }

    public function testExecuteThrowsExceptionWhenNotOrder(): void
    {
        $this->expectException(WorkflowException::class);
        $this->expectExceptionMessage('Only Order entity is supported');

        $data = new ActionData();
        $data->offsetSet('data', new \stdClass());

        $this->operation->execute($data);
    }

    public function testExecuteNoLineItems(): void
    {
        $order = new Order();

        $data = new ActionData();
        $data->offsetSet('data', $order);

        $this->lineItemsFactory->expects($this->once())
            ->method('create')
            ->with($order)
            ->willReturn(new ArrayCollection());

        $this->startCheckout->expects($this->never())
            ->method('execute');
        $this->checkoutLineItemsProvider->expects($this->never())
            ->method('getProductSkusWithDifferences');

        $this->actionExecutor->expects($this->once())
            ->method('executeAction')
            ->with(
                'flash_message',
                [
                    'message' => 'oro.checkout.frontend.checkout.cannot_create_reorder_no_line_items',
                    'type' => 'warning'
                ]
            );

        $this->operation->execute($data);
    }

    public function testExecute(): void
    {
        $order = new Order();
        $checkout = new Checkout();

        $data = new ActionData();
        $data->offsetSet('data', $order);

        $this->lineItemsFactory->expects($this->once())
            ->method('create')
            ->with($order)
            ->willReturn(new ArrayCollection([$this->createMock(CheckoutLineItem::class)]));

        $this->startCheckout->expects($this->once())
            ->method('execute')
            ->with(
                ['order' => $order],
                true,
                [],
                ['allow_manual_source_remove' => false, 'remove_source' => false],
                true,
                true,
                null,
                true
            )
            ->willReturn([
                'checkout' => $checkout,
                'errors' => [],
                'redirectUrl' => 'http://test.url'
            ]);

        $this->checkoutLineItemsProvider->expects($this->once())
            ->method('getProductSkusWithDifferences')
            ->willReturn([]);

        $this->actionExecutor->expects($this->never())
            ->method('executeAction');

        $this->operation->execute($data);

        $this->assertSame($checkout, $data->offsetGet('checkout'));
        $this->assertSame([], $data->offsetGet('errors'));
        $this->assertSame('http://test.url', $data->offsetGet('redirectUrl'));
    }

    public function testExecuteChangedSkus(): void
    {
        $order = new Order();
        $checkout = new Checkout();

        $data = new ActionData();
        $data->offsetSet('data', $order);

        $this->lineItemsFactory->expects($this->once())
            ->method('create')
            ->with($order)
            ->willReturn(new ArrayCollection([$this->createMock(CheckoutLineItem::class)]));

        $this->startCheckout->expects($this->once())
            ->method('execute')
            ->with(
                ['order' => $order],
                true,
                [],
                ['allow_manual_source_remove' => false, 'remove_source' => false],
                true,
                true,
                null,
                true
            )
            ->willReturn([
                'checkout' => $checkout
            ]);

        $this->checkoutLineItemsProvider->expects($this->once())
            ->method('getProductSkusWithDifferences')
            ->willReturn(['sku1', 'sku2']);

        $this->actionExecutor->expects($this->once())
            ->method('executeAction')
            ->with(
                'flash_message',
                [
                    'message' => 'oro.checkout.frontend.checkout.some_changes_in_line_items',
                    'message_parameters' => [
                        'skus' => 'sku1, sku2'
                    ],
                    'type' => 'warning'
                ]
            );

        $this->operation->execute($data);

        $this->assertSame($checkout, $data->offsetGet('checkout'));
        $this->assertSame([], $data->offsetGet('errors'));
        $this->assertNull($data->offsetGet('redirectUrl'));
    }
}
