<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Event;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Event\CheckoutTransitionAfterEvent;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class CheckoutTransitionAfterEventTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $transition = $this->createMock(Transition::class);
        $isAllowed = false;
        $errors = new ArrayCollection(['sample_error']);
        $properties = [
            ['workflowItem', $workflowItem, false],
            ['transition', $transition, false],
            ['allowed', $isAllowed, false],
            ['errors', $errors, false],
        ];

        $event = new CheckoutTransitionAfterEvent($workflowItem, $transition, $isAllowed, $errors);

        $this->assertPropertyAccessors($event, $properties);
    }
}
