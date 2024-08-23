<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\B2bFlowCheckout\Transition;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Action\DefaultShippingMethodSetterInterface;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\Transition\BackToShippingMethod;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceInterface;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BackToShippingMethodTest extends TestCase
{
    private ConfigProvider|MockObject $configProvider;
    private DefaultShippingMethodSetterInterface|MockObject $defaultShippingMethodSetter;
    private TransitionServiceInterface|MockObject $baseTransition;
    private BackToShippingMethod $transition;

    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->defaultShippingMethodSetter = $this->createMock(DefaultShippingMethodSetterInterface::class);
        $this->baseTransition = $this->createMock(TransitionServiceInterface::class);

        $this->transition = new BackToShippingMethod(
            $this->configProvider,
            $this->defaultShippingMethodSetter,
            $this->baseTransition
        );
    }

    public function testIsPreConditionAllowed(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $errors = new ArrayCollection();

        $this->baseTransition->expects($this->once())
            ->method('isPreConditionAllowed')
            ->with($workflowItem, $errors)
            ->willReturn(true);

        $result = $this->transition->isPreConditionAllowed($workflowItem, $errors);
        $this->assertTrue($result);
    }

    public function testExecuteWithoutMultiShipping(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $checkout = $this->createMock(Checkout::class);

        $workflowItem->expects($this->once())
            ->method('getEntity')
            ->willReturn($checkout);

        $this->baseTransition->expects($this->once())
            ->method('execute')
            ->with($workflowItem);

        $this->configProvider->expects($this->once())
            ->method('isMultiShippingEnabled')
            ->willReturn(false);

        $this->defaultShippingMethodSetter->expects($this->once())
            ->method('setDefaultShippingMethod')
            ->with($checkout);

        $workflowItemData = $this->createMock(WorkflowData::class);

        $workflowItem->expects($this->once())
            ->method('getData')
            ->willReturn($workflowItemData);

        $workflowItemData->expects($this->once())
            ->method('offsetSet')
            ->with('shipping_data_ready', false);

        $this->transition->execute($workflowItem);
    }

    public function testExecuteWithMultiShipping(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $checkout = $this->createMock(Checkout::class);

        $workflowItem->expects($this->once())
            ->method('getEntity')
            ->willReturn($checkout);

        $this->baseTransition->expects($this->once())
            ->method('execute')
            ->with($workflowItem);

        $this->configProvider->expects($this->once())
            ->method('isMultiShippingEnabled')
            ->willReturn(true);

        $this->defaultShippingMethodSetter
            ->expects($this->never())
            ->method('setDefaultShippingMethod');

        $workflowItemData = $this->createMock(WorkflowData::class);

        $workflowItem->expects($this->once())
            ->method('getData')
            ->willReturn($workflowItemData);

        $workflowItemData->expects($this->once())
            ->method('offsetSet')
            ->with('shipping_data_ready', false);

        $this->transition->execute($workflowItem);
    }
}
