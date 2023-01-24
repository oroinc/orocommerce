<?php

namespace Oro\Bundle\CheckoutBundle\Helper;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\GroupedCheckoutLineItemsProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\SplitCheckoutProvider;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;

/**
 * Helper to decide if checkout line items grouping needs invalidation
 */
class CheckoutLineItemGroupingInvalidationHelper
{
    private const CHECKOUT_INVALIDATION_HOURS = 24;

    private ConfigProvider $multiShippingConfigProvider;
    private GroupedCheckoutLineItemsProvider $groupedCheckoutLineItemsProvider;

    public function __construct(
        ConfigProvider $multiShippingConfigProvider,
        GroupedCheckoutLineItemsProvider $groupedCheckoutLineItemsProvider
    ) {
        $this->multiShippingConfigProvider = $multiShippingConfigProvider;
        $this->groupedCheckoutLineItemsProvider = $groupedCheckoutLineItemsProvider;
    }

    public function shouldInvalidateLineItemGrouping(WorkflowItem $workflowItem): bool
    {
        if (!$this->multiShippingConfigProvider->isLineItemsGroupingEnabled()) {
            return false;
        }

        $dateInterval = (new \DateTime('now', new \DateTimeZone('UTC')))
            ->diff($workflowItem->getUpdated());
        $hoursInterval = $dateInterval->days * 24 + $dateInterval->h;

        return $hoursInterval >= self::CHECKOUT_INVALIDATION_HOURS;
    }

    public function invalidateLineItemGrouping(Checkout $checkout, WorkflowItem $workflowItem): void
    {
        $workflowItem->getData()->offsetSet(
            SplitCheckoutProvider::GROUPED_LINE_ITEMS_ATTRIBUTE,
            $this->groupedCheckoutLineItemsProvider->getGroupedLineItemsIds($checkout)
        );

        $workflowItem->setUpdated();
    }
}
