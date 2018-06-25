<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Component\Layout\ContextInterface;

trait CheckoutAwareContextTrait
{
    /**
     * @param Checkout $checkout
     * @param WorkflowItem $workflowItem
     * @return ContextInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function prepareContext(Checkout $checkout, WorkflowItem $workflowItem)
    {
        $context = $this->createMock('Oro\Component\Layout\ContextInterface');

        $data = $this->getMockBuilder('Oro\Component\Layout\ContextDataCollection')
            ->disableOriginalConstructor()
            ->getMock();

        $data->expects($this->exactly(1))
            ->method('get')
            ->willReturnMap(
                [
                    ['checkout', $checkout],
                    ['workflowItem', $workflowItem]
                ]
            );
        $context->expects($this->exactly(1))
            ->method('data')
            ->will($this->returnValue($data));

        return $context;
    }
}
