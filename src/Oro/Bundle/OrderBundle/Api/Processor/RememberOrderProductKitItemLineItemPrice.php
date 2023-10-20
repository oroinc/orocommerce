<?php

namespace Oro\Bundle\OrderBundle\Api\Processor;

use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;

/**
 * Stores the submitted price of {@see OrderProductKitItemLineItem} ("price" and "currency" fields) to the context
 * and sets a fake price to be sure the price value validation will pass.
 * The stored price is used by {@see FillOrderProductKitItemLineItemPrice} processor to validate
 * that it equals to a calculated price.
 */
class RememberOrderProductKitItemLineItemPrice extends RememberOrderLineItemPrice
{
    public const SUBMITTED_PRICE = 'order_product_kit_item_line_item_submitted_price';
}
