<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\BaseTransition;

use Oro\Bundle\CheckoutBundle\Workflow\BaseTransition\UnlockAndRecalculate;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use PHPUnit\Framework\TestCase;

class UnlockAndRecalculateTest extends TestCase
{
    private UnlockAndRecalculate $transition;

    protected function setUp(): void
    {
        $this->transition = new UnlockAndRecalculate();
    }

    public function testExecute(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowData = new WorkflowData([
            'payment_method' => 'payment_method',
            'payment_in_progress' => true,
            'shipping_data_ready' => true
        ]);

        $workflowItem->expects($this->once())
            ->method('getData')
            ->willReturn($workflowData);

        $this->transition->execute($workflowItem);

        $this->assertNull($workflowData->offsetGet('payment_method'));
        $this->assertFalse($workflowData->offsetGet('payment_in_progress'));
        $this->assertFalse($workflowData->offsetGet('shipping_data_ready'));
    }
}
