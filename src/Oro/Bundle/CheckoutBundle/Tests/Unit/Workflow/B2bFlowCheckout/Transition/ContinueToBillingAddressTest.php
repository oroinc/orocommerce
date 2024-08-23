<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\B2bFlowCheckout\Transition;

use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\Transition\ContinueToBillingAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ContinueToBillingAddressTest extends TestCase
{
    private ActionExecutor|MockObject $actionExecutor;
    private ContinueToBillingAddress $transition;

    protected function setUp(): void
    {
        $this->actionExecutor = $this->createMock(ActionExecutor::class);
        $this->transition = new ContinueToBillingAddress($this->actionExecutor);
    }

    public function testExecuteWithCustomerUserNotGuest()
    {
        $checkout = new Checkout();
        $checkout->setCustomerUser($this->createMock(CustomerUser::class));
        $checkout->getCustomerUser()->method('isGuest')->willReturn(false);

        $workflowData = new WorkflowData();
        $workflowData['customerConsents'] = 'some_consents';

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->method('getEntity')->willReturn($checkout);
        $workflowItem->method('getData')->willReturn($workflowData);

        $this->actionExecutor->expects($this->once())
            ->method('executeAction')
            ->with('save_accepted_consents', ['acceptedConsents' => 'some_consents']);

        $this->transition->execute($workflowItem);

        // Verify that customerConsents is cleared
        $this->assertNull($workflowData['customerConsents']);
    }

    public function testExecuteWithCustomerUserIsGuest()
    {
        $checkout = new Checkout();
        $checkout->setCustomerUser($this->createMock(CustomerUser::class));
        $checkout->getCustomerUser()->method('isGuest')->willReturn(true);

        $workflowData = new WorkflowData();
        $workflowData['customerConsents'] = 'some_consents';

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->method('getEntity')->willReturn($checkout);
        $workflowItem->method('getData')->willReturn($workflowData);

        $this->actionExecutor->expects($this->once())
            ->method('executeAction')
            ->with('save_accepted_consents', ['acceptedConsents' => 'some_consents']);

        $this->transition->execute($workflowItem);

        // Verify that customerConsents remains unchanged
        $this->assertEquals('some_consents', $workflowData['customerConsents']);
    }

    public function testExecuteWithNoCustomerUser()
    {
        $checkout = new Checkout();
        $checkout->setCustomerUser(null);

        $workflowData = new WorkflowData();
        $workflowData['customerConsents'] = 'some_consents';

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->method('getEntity')->willReturn($checkout);
        $workflowItem->method('getData')->willReturn($workflowData);

        $this->actionExecutor->expects($this->once())
            ->method('executeAction')
            ->with('save_accepted_consents', ['acceptedConsents' => 'some_consents']);

        $this->transition->execute($workflowItem);

        // Verify that customerConsents remains unchanged
        $this->assertEquals('some_consents', $workflowData['customerConsents']);
    }
}
