<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\ActionGroup;

use Oro\Bundle\CheckoutBundle\Action\MultiShipping\SubOrderMultiShippingMethodSetter;
use Oro\Bundle\CheckoutBundle\DataProvider\Converter\CheckoutLineItemsConverter;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\GroupedCheckoutLineItemsProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\SubOrderOrganizationProviderInterface;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\SubOrderOwnerProviderInterface;
use Oro\Bundle\CheckoutBundle\Shipping\Method\CheckoutSubOrderShippingPriceProvider;
use Oro\Bundle\CheckoutBundle\Splitter\MultiShipping\CheckoutSplitter;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Total\TotalHelper;
use Oro\Bundle\PromotionBundle\Manager\AppliedPromotionManager;

/**
 * Checkout workflow actions to be executed to create split orders.
 */
class SplitOrderActions implements SplitOrderActionsInterface
{
    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        private readonly OrderActionsInterface $orderActions,
        private readonly TotalHelper $totalHelper,
        private readonly CheckoutSplitter $checkoutSplitter,
        private readonly GroupedCheckoutLineItemsProvider $groupedLineItemsProvider,
        private readonly SubOrderOwnerProviderInterface $subOrderOwnerProvider,
        private readonly SubOrderOrganizationProviderInterface $subOrderOrganizationProvider,
        private readonly SubOrderMultiShippingMethodSetter $subOrderMultiShippingMethodSetter,
        private readonly CheckoutSubOrderShippingPriceProvider $checkoutSubOrderShippingPriceProvider,
        private readonly AppliedPromotionManager $appliedPromotionManager,
        private readonly ConfigProvider $configProvider,
        private readonly CheckoutLineItemsConverter $checkoutLineItemsConverter
    ) {
    }

    #[\Override]
    public function placeOrder(Checkout $checkout, ?array $groupedLineItemsIds): Order
    {
        if ($groupedLineItemsIds && $this->configProvider->isCreateSubOrdersForEachGroupEnabled()) {
            $this->checkoutLineItemsConverter->setReuseLineItems(true);
            try {
                $order = $this->orderActions->placeOrder($checkout);
                $this->createChildOrders($checkout, $order, $groupedLineItemsIds);
            } finally {
                $this->checkoutLineItemsConverter->setReuseLineItems(false);
            }

            return $order;
        }

        return $this->orderActions->placeOrder($checkout);
    }

    #[\Override]
    public function createChildOrders(
        Checkout $checkout,
        Order $order,
        array $groupedLineItemsIds
    ): void {
        $childOrderIdentifierTemplate = $order->getIdentifier() . '-';

        $i = 1;
        $splitCheckouts = $this->checkoutSplitter->split(
            $checkout,
            $this->groupedLineItemsProvider->getGroupedLineItemsByIds($checkout, $groupedLineItemsIds)
        );
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
            );
            $childOrder->setParent($order);
            $order->addSubOrder($childOrder);

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
}
