<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\B2bFlowCheckout\ActionGroup;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Action\MultiShipping\SubOrderMultiShippingMethodSetter;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\GroupedCheckoutLineItemsProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\SubOrderOrganizationProviderInterface;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\SubOrderOwnerProviderInterface;
use Oro\Bundle\CheckoutBundle\Shipping\Method\CheckoutSubOrderShippingPriceProvider;
use Oro\Bundle\CheckoutBundle\Splitter\MultiShipping\CheckoutSplitter;
use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\ActionGroup\OrderActions;
use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\ActionGroup\SplitOrderActions;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Total\TotalHelper;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PromotionBundle\Manager\AppliedPromotionManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SplitOrderActionsTest extends TestCase
{
    private OrderActions|MockObject $orderActions;
    private TotalHelper|MockObject $totalHelper;
    private CheckoutSplitter|MockObject $checkoutSplitter;
    private GroupedCheckoutLineItemsProvider|MockObject $groupedLineItemsProvider;
    private SubOrderOwnerProviderInterface|MockObject $subOrderOwnerProvider;
    private SubOrderOrganizationProviderInterface|MockObject $subOrderOrganizationProvider;
    private SubOrderMultiShippingMethodSetter|MockObject $subOrderMultiShippingMethodSetter;
    private CheckoutSubOrderShippingPriceProvider|MockObject $checkoutSubOrderShippingPriceProvider;
    private AppliedPromotionManager|MockObject $appliedPromotionManager;
    private SplitOrderActions $splitOrderActions;

    #[\Override]
    protected function setUp(): void
    {
        $this->orderActions = $this->createMock(OrderActions::class);
        $this->totalHelper = $this->createMock(TotalHelper::class);
        $this->checkoutSplitter = $this->createMock(CheckoutSplitter::class);
        $this->groupedLineItemsProvider = $this->createMock(GroupedCheckoutLineItemsProvider::class);
        $this->subOrderOwnerProvider = $this->createMock(SubOrderOwnerProviderInterface::class);
        $this->subOrderOrganizationProvider = $this->createMock(SubOrderOrganizationProviderInterface::class);
        $this->subOrderMultiShippingMethodSetter = $this->createMock(SubOrderMultiShippingMethodSetter::class);
        $this->checkoutSubOrderShippingPriceProvider = $this->createMock(CheckoutSubOrderShippingPriceProvider::class);
        $this->appliedPromotionManager = $this->createMock(AppliedPromotionManager::class);

        $this->splitOrderActions = new SplitOrderActions(
            $this->orderActions,
            $this->totalHelper,
            $this->checkoutSplitter,
            $this->groupedLineItemsProvider,
            $this->subOrderOwnerProvider,
            $this->subOrderOrganizationProvider,
            $this->subOrderMultiShippingMethodSetter,
            $this->checkoutSubOrderShippingPriceProvider,
            $this->appliedPromotionManager
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCreateChildOrders(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $order = $this->createMock(Order::class);
        $order->expects($this->any())
            ->method('getIdentifier')
            ->willReturn('O1');
        $childOrder1 = $this->createMock(Order::class);
        $childOrder1->expects($this->once())
            ->method('setIdentifier')
            ->with('O1-1');
        $childOrder2 = $this->createMock(Order::class);
        $childOrder2->expects($this->once())
            ->method('setIdentifier')
            ->with('O1-2');

        $billingAddress1 = $this->createMock(OrderAddress::class);
        $billingAddress2 = $this->createMock(OrderAddress::class);
        $shippingAddress1 = $this->createMock(OrderAddress::class);
        $shippingAddress2 = $this->createMock(OrderAddress::class);

        $groupedLineItemsIds = [1, 2, 3];
        $splitCheckout1 = $this->createMock(Checkout::class);
        $splitCheckout1->expects($this->any())
            ->method('getBillingAddress')
            ->willReturn($billingAddress1);
        $splitCheckout1->expects($this->any())
            ->method('getShippingAddress')
            ->willReturn($shippingAddress1);
        $splitCheckout1->expects($this->once())
            ->method('setShippingCost')
            ->with(Price::create(100, 'USD'));
        $splitCheckout2 = $this->createMock(Checkout::class);
        $splitCheckout2->expects($this->any())
            ->method('getBillingAddress')
            ->willReturn($billingAddress2);
        $splitCheckout2->expects($this->once())
            ->method('setShippingCost')
            ->with(Price::create(200, 'USD'));
        $splitCheckout2->expects($this->any())
            ->method('getShippingAddress')
            ->willReturn($shippingAddress2);

        $splitCheckouts = [
            'group1' => $splitCheckout1,
            'group2' => $splitCheckout2,
        ];

        $lineItems1 = new ArrayCollection([$this->createMock(CheckoutLineItem::class)]);
        $lineItems2 = new ArrayCollection([$this->createMock(CheckoutLineItem::class)]);

        $splitCheckout1->method('getLineItems')->willReturn($lineItems1);
        $splitCheckout2->method('getLineItems')->willReturn($lineItems2);

        $this->groupedLineItemsProvider
            ->expects($this->once())
            ->method('getGroupedLineItemsByIds')
            ->with($checkout, $groupedLineItemsIds)
            ->willReturn([$lineItems1, $lineItems2]);

        $this->checkoutSplitter
            ->expects($this->once())
            ->method('split')
            ->with($checkout, [$lineItems1, $lineItems2])
            ->willReturn($splitCheckouts);

        $organization = $this->createMock(Organization::class);

        $this->subOrderOrganizationProvider
            ->expects($this->exactly(2))
            ->method('getOrganization')
            ->withConsecutive(
                [$lineItems1, 'group1'],
                [$lineItems2, 'group2']
            )
            ->willReturn($organization);

        $this->subOrderMultiShippingMethodSetter
            ->expects($this->exactly(2))
            ->method('setShippingMethod')
            ->withConsecutive(
                [$checkout, $splitCheckout1, 'group1'],
                [$checkout, $splitCheckout2, 'group2'],
            );

        $this->checkoutSubOrderShippingPriceProvider
            ->expects($this->exactly(2))
            ->method('getPrice')
            ->withConsecutive(
                [$splitCheckout1, $organization],
                [$splitCheckout2, $organization],
            )
            ->willReturnOnConsecutiveCalls(
                Price::create(100, 'USD'),
                Price::create(200, 'USD')
            );

        $this->orderActions
            ->expects($this->exactly(2))
            ->method('createOrderByCheckout')
            ->withConsecutive(
                [$splitCheckout1, $billingAddress1, $shippingAddress1],
                [$splitCheckout2, $billingAddress2, $shippingAddress2],
            )
            ->willReturnOnConsecutiveCalls(
                $childOrder1,
                $childOrder2
            );

        $this->orderActions
            ->expects($this->exactly(3))
            ->method('flushOrder')
            ->withConsecutive(
                [$childOrder1],
                [$childOrder2],
                [$order]
            );

        $this->appliedPromotionManager
            ->expects($this->once())
            ->method('createAppliedPromotions')
            ->with($order, true);

        $this->totalHelper
            ->expects($this->once())
            ->method('fill')
            ->with($order);

        $this->splitOrderActions->createChildOrders($checkout, $order, $groupedLineItemsIds);
    }
}
