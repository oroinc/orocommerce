<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\B2bFlowCheckout\Transition;

use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\GroupedCheckoutLineItemsProvider;
use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\Transition\PaidPartially;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PaidPartiallyTest extends TestCase
{
    use EntityTrait;

    private ActionExecutor|MockObject $actionExecutor;
    private UrlGeneratorInterface|MockObject $urlGenerator;
    private GroupedCheckoutLineItemsProvider|MockObject $groupedCheckoutLineItemsProvider;

    private PaidPartially $transition;

    protected function setUp(): void
    {
        $this->actionExecutor = $this->createMock(ActionExecutor::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->groupedCheckoutLineItemsProvider = $this->createMock(GroupedCheckoutLineItemsProvider::class);

        $this->transition = new PaidPartially(
            $this->actionExecutor,
            $this->urlGenerator,
            $this->groupedCheckoutLineItemsProvider
        );
    }

    public function testExecuteWithOrder()
    {
        $order = $this->getEntity(Order::class, ['id' => 1]);
        $order->setIdentifier('ORD-001');

        $checkout = new Checkout();
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->method('getEntity')->willReturn($checkout);

        $data = new WorkflowData();
        $data->offsetSet('order', $order);
        $workflowItem->method('getData')->willReturn($data);

        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->with('oro_order_frontend_view', ['id' => 1])
            ->willReturn('http://example.com/order/1');

        $this->actionExecutor->expects($this->exactly(2))
            ->method('executeAction')
            ->withConsecutive(
                [
                    'flash_message',
                    [
                        'message' => 'oro.checkout.workflow.condition.payment_has_not_fully_been_processed.message',
                        'message_parameters' => [
                            'orderIdentifier' => 'ORD-001',
                            'orderViewLink' => 'http://example.com/order/1'
                        ],
                        'type' => 'warning'
                    ]
                ],
                [
                    'actualize_line_items_by_unpaid_suborders',
                    [
                        'order' => $order,
                        'checkout' => $checkout
                    ]
                ]
            );

        $this->groupedCheckoutLineItemsProvider->expects($this->once())
            ->method('getGroupedLineItemsIds')
            ->with($checkout)
            ->willReturn(['line_item_group_1', 'line_item_group_2']);

        $this->transition->execute($workflowItem);

        $this->assertEquals(['line_item_group_1', 'line_item_group_2'], $data->offsetGet('grouped_line_items'));
        $this->assertNull($data->offsetGet('payment_method'));
        $this->assertFalse($data->offsetGet('payment_in_progress'));
        $this->assertNull($data->offsetGet('order'));
    }

    public function testExecuteWithoutOrder()
    {
        $checkout = new Checkout();
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->method('getEntity')->willReturn($checkout);

        $data = new WorkflowData();
        $workflowItem->method('getData')->willReturn($data);

        $this->urlGenerator->expects($this->never())
            ->method('generate');

        $this->actionExecutor->expects($this->never())
            ->method('executeAction');

        $this->groupedCheckoutLineItemsProvider->expects($this->never())
            ->method('getGroupedLineItemsIds');

        $this->transition->execute($workflowItem);

        $this->assertNull($data->offsetGet('payment_method'));
        $this->assertFalse($data->offsetGet('payment_in_progress'));
        $this->assertNull($data->offsetGet('order'));
    }
}
