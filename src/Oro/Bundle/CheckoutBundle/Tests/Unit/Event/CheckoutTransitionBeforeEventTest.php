<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Event;

use Oro\Bundle\CheckoutBundle\Event\CheckoutTransitionBeforeEvent;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class CheckoutTransitionBeforeEventTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $transition = $this->createMock(Transition::class);
        $properties = [['workflowItem', $workflowItem, false], ['transition', $transition, false]];

        $event = new CheckoutTransitionBeforeEvent($workflowItem, $transition);

        $this->assertPropertyAccessors($event, $properties);
    }
}
