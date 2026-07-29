<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\ActionGroup;

use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;

/**
 * Expects checkout as input. Checks order line items created from checkout for 2 cases:
 * 1) if order line items (at least one) can be added to the checkout and sets $.orderLineItemsNotEmpty variable;
 * 2) if there are no order line items can be added to order, then checks if order line items (at least one)
 *    can be added to RFP and sets $.orderLineItemsNotEmptyForRfp variable.
 */
class OrderLineItemsNotEmpty implements OrderLineItemsNotEmptyInterface
{
    private const string ORDER_VISIBILITY = 'oro_order.frontend_product_visibility';
    private const string RFP_VISIBILITY = 'oro_rfp.frontend_product_visibility';

    public function __construct(
        private ActionExecutor $actionExecutor
    ) {
    }

    #[\Override]
    public function execute(Checkout $checkout): array
    {
        $order = $this->getOrderLineItemsData($checkout, self::ORDER_VISIBILITY);
        $orderLineItems = $order['attribute'];
        $orderLineItemsNotEmpty = count($orderLineItems) > 0;

        $orderLineItemsForRfp = $orderLineItemsNotEmpty
            ? []
            : $this->getOrderLineItemsData($checkout, self::RFP_VISIBILITY)['attribute'];

        return [
            'orderLineItems' => $orderLineItems,
            'orderLineItemsNotEmpty' => $orderLineItemsNotEmpty,
            'orderLineItemsForRfp' => $orderLineItemsForRfp,
            'orderLineItemsNotEmptyForRfp' => $orderLineItemsNotEmpty || count($orderLineItemsForRfp) > 0,
            'orderLineItemsSkippedReasons' => $order['reasons_attribute'],
        ];
    }

    private function getOrderLineItemsData(Checkout $checkout, string $configVisibilityPath): mixed
    {
        return $this->actionExecutor->executeAction(
            'get_order_line_items',
            [
                'checkout' => $checkout,
                'disable_price_filter' => false,
                'config_visibility_path' => $configVisibilityPath,
                'attribute' => null,
                'reasons_attribute' => null,
            ]
        );
    }
}
