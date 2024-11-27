<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\B2bFlowCheckoutSinglePage\Transition;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\AddressActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\UpdateShippingPriceInterface;
use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckoutSinglePage\Transition\BaseStateAwareTransition;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Manager\OrderAddressManager;
use Oro\Bundle\OrderBundle\Manager\TypedOrderAddressCollection;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BaseStateAwareTransitionTest extends TestCase
{
    private AddressActionsInterface|MockObject $addressActions;
    private OrderAddressManager|MockObject $orderAddressManager;
    private UpdateShippingPriceInterface|MockObject $updateShippingPrice;
    private BaseStateAwareTransition $transition;

    #[\Override]
    protected function setUp(): void
    {
        $this->addressActions = $this->createMock(AddressActionsInterface::class);
        $this->orderAddressManager = $this->createMock(OrderAddressManager::class);
        $this->updateShippingPrice = $this->createMock(UpdateShippingPriceInterface::class);

        $this->transition = new BaseStateAwareTransition(
            $this->addressActions,
            $this->orderAddressManager,
            $this->updateShippingPrice
        );
    }

    public function testExecute(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowData = new WorkflowData([
            'ship_to_billing_address' => true,
            'disallow_shipping_address_edit' => false,
        ]);
        $checkout = $this->createMock(Checkout::class);
        $billingAddress = $this->createMock(OrderAddress::class);
        $shippingAddress = $this->createMock(OrderAddress::class);

        $workflowItem->expects($this->once())
            ->method('getEntity')
            ->willReturn($checkout);

        $workflowItem->expects($this->once())
            ->method('getData')
            ->willReturn($workflowData);

        $checkout->expects($this->once())
            ->method('getBillingAddress')
            ->willReturn(null);

        $checkout->expects($this->once())
            ->method('getShippingAddress')
            ->willReturn(null);

        $groupedBillingAddresses = $this->createMock(TypedOrderAddressCollection::class);
        $groupedBillingAddresses->expects($this->once())
            ->method('getDefaultAddress')
            ->willReturn($billingAddress);
        $groupedShippingAddresses = $this->createMock(TypedOrderAddressCollection::class);
        $groupedShippingAddresses->expects($this->once())
            ->method('getDefaultAddress')
            ->willReturn($shippingAddress);

        $this->orderAddressManager->expects($this->exactly(2))
            ->method('getGroupedAddresses')
            ->withConsecutive(
                [$checkout, 'billing'],
                [$checkout, 'shipping']
            )
            ->willReturn(
                $groupedBillingAddresses,
                $groupedShippingAddresses
            );

        $this->orderAddressManager->expects($this->exactly(2))
            ->method('updateFromAbstract')
            ->withConsecutive(
                [$billingAddress],
                [$shippingAddress]
            )
            ->willReturnOnConsecutiveCalls(
                $billingAddress,
                $shippingAddress
            );

        $checkout->expects($this->once())
            ->method('setBillingAddress')
            ->with($billingAddress);

        $checkout->expects($this->once())
            ->method('setShippingAddress')
            ->with($shippingAddress);

        $this->addressActions->expects($this->once())
            ->method('updateBillingAddress')
            ->with($checkout, false)
            ->willReturn(true);

        $this->addressActions->expects($this->once())
            ->method('updateShippingAddress')
            ->with($checkout);

        $this->updateShippingPrice->expects($this->once())
            ->method('execute')
            ->with($checkout);

        $this->transition->execute($workflowItem);

        $this->assertTrue($workflowData->offsetGet('billing_address_has_shipping'));
    }
}
