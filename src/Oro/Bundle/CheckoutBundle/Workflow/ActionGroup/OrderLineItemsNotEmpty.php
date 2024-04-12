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
    public function __construct(
        private ActionExecutor $actionExecutor
    ) {
    }

    public function execute(Checkout $checkout): array
    {
        $orderLineItemsNotEmpty = false;
        $getLineItemsResult = $this->actionExecutor->executeAction(
            'get_order_line_items',
            [
                'checkout' => $checkout,
                'disable_price_filter' => false,
                'config_visibility_path' => 'oro_order.frontend_product_visibility',
                'attribute' => null
            ]
        );
        $orderLineItems = $getLineItemsResult['attribute'];

        $orderLineItemsForRfp = [];
        $orderLineItemsNotEmptyForRfp = false;
        if (count($orderLineItems) > 0) {
            $orderLineItemsNotEmpty = true;
            $orderLineItemsNotEmptyForRfp = true;
        }

        if (!$orderLineItemsNotEmptyForRfp && !$orderLineItemsNotEmpty) {
            $getLineItemsResult = $this->actionExecutor->executeAction(
                'get_order_line_items',
                [
                    'checkout' => $checkout,
                    'disable_price_filter' => false,
                    'config_visibility_path' => 'oro_rfp.frontend_product_visibility',
                    'attribute' => null
                ]
            );
            $orderLineItemsForRfp = $getLineItemsResult['attribute'];
            $orderLineItemsNotEmptyForRfp = count($orderLineItemsForRfp) > 0;
        }

        return [
            'orderLineItems' => $orderLineItems,
            'orderLineItemsNotEmpty' => $orderLineItemsNotEmpty,
            'orderLineItemsForRfp' => $orderLineItemsForRfp,
            'orderLineItemsNotEmptyForRfp' => $orderLineItemsNotEmptyForRfp,
        ];
    }
}
