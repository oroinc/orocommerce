<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\ActionGroup;

use Oro\Bundle\CheckoutBundle\Action\MultiShipping\SubOrderMultiShippingMethodSetter;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\GroupedCheckoutLineItemsProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\SubOrderOrganizationProviderInterface;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\SubOrderOwnerProviderInterface;
use Oro\Bundle\CheckoutBundle\Shipping\Method\CheckoutSubOrderShippingPriceProvider;
use Oro\Bundle\CheckoutBundle\Splitter\MultiShipping\CheckoutSplitter;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Total\TotalHelper;
use Oro\Bundle\PromotionBundle\Manager\AppliedPromotionManager;

class SplitOrderActions
{
    public function __construct(
        private OrderActions $orderActions,
        private TotalHelper $totalHelper,
        private CheckoutSplitter $checkoutSplitter,
        private GroupedCheckoutLineItemsProvider $groupedLineItemsProvider,
        private SubOrderOwnerProviderInterface $subOrderOwnerProvider,
        private SubOrderOrganizationProviderInterface $subOrderOrganizationProvider,
        private SubOrderMultiShippingMethodSetter $subOrderMultiShippingMethodSetter,
        private CheckoutSubOrderShippingPriceProvider $checkoutSubOrderShippingPriceProvider,
        private AppliedPromotionManager $appliedPromotionManager
    ) {
    }

    public function createChildOrders(
        Checkout $checkout,
        Order $order,
        array $groupedLineItemsIds
    ): void {
        $childOrderIdentifierTemplate = $order->getIdentifier() . '-';

        $i = 1;
        $splitCheckouts = $this->splitCheckouts($checkout, $groupedLineItemsIds);
        foreach ($splitCheckouts as $groupingPath => $splitCheckout) {
            $splitCheckoutLineItems = $splitCheckout->getLineItems();
            $childOrderOrganization = $this->subOrderOrganizationProvider->getOrganization(
                $splitCheckoutLineItems,
                $groupingPath
            );
            $this->subOrderMultiShippingMethodSetter->setShippingMethod($checkout, $splitCheckout, $groupingPath);
            $splitCheckout->setShippingCost(
                $this->checkoutSubOrderShippingPriceProvider->getPrice($splitCheckout, $childOrderOrganization)
            );

            $childOrder = $this->orderActions->createOrderByCheckout(
                $splitCheckout,
                $splitCheckout->getBillingAddress(),
                $splitCheckout->getShippingAddress()
            )['order'];
            $childOrder->setParent($order);

            $childOrder->setIdentifier($childOrderIdentifierTemplate . $i);
            $i++;

            $childOrder->setOwner($this->subOrderOwnerProvider->getOwner($splitCheckoutLineItems, $groupingPath));
            $childOrder->setOrganization($childOrderOrganization);

            $this->orderActions->flushOrder($childOrder);
        }

        $this->appliedPromotionManager->createAppliedPromotions($order, true);
        $this->totalHelper->fill($order);

        $this->orderActions->flushOrder($order);
    }

    /**
     * @return array|Checkout[]
     */
    private function splitCheckouts(Checkout $checkout, array $groupedLineItemsIds): array
    {
        $splitItems = $this->groupedLineItemsProvider->getGroupedLineItemsByIds($checkout, $groupedLineItemsIds);

        return $this->checkoutSplitter->split($checkout, $splitItems);
    }
}
