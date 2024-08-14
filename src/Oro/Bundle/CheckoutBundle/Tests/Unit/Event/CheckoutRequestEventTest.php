<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Event;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Event\CheckoutRequestEvent;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class CheckoutRequestEventTest extends TestCase
{
    public function testConstructorAndGetters()
    {
        $request = $this->createMock(Request::class);
        $checkout = $this->createMock(Checkout::class);

        $event = new CheckoutRequestEvent($request, $checkout);

        $this->assertSame($request, $event->getRequest());
        $this->assertSame($checkout, $event->getCheckout());
        $this->assertNull($event->getWorkflowStep());
    }

    public function testSetAndGetWorkflowStep()
    {
        $request = $this->createMock(Request::class);
        $checkout = $this->createMock(Checkout::class);
        $workflowStep = $this->createMock(WorkflowStep::class);

        $event = new CheckoutRequestEvent($request, $checkout);

        $event->setWorkflowStep($workflowStep);
        $this->assertSame($workflowStep, $event->getWorkflowStep());

        $event->setWorkflowStep(null);
        $this->assertNull($event->getWorkflowStep());
    }
}
