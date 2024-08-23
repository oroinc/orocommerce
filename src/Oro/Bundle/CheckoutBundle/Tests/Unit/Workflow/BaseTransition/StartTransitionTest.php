<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\BaseTransition;

use Oro\Bundle\CheckoutBundle\Workflow\BaseTransition\StartTransition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use PHPUnit\Framework\TestCase;

class StartTransitionTest extends TestCase
{
    private StartTransition $transition;

    protected function setUp(): void
    {
        $this->transition = new StartTransition();
    }

    public function testExecute(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowData = new WorkflowData([
            'shipping_method' => 'shippingMethod',
            'payment_save_for_later' => true
        ]);

        $workflowItem->expects($this->once())
            ->method('getData')
            ->willReturn($workflowData);

        $this->transition->execute($workflowItem);

        $this->assertNull($workflowData->offsetGet('shipping_method'));
        $this->assertNull($workflowData->offsetGet('payment_save_for_later'));
    }
}
