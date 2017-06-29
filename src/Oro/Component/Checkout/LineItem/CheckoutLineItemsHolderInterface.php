<?php

namespace Oro\Component\Checkout\LineItem;

interface CheckoutLineItemsHolderInterface
{
    /**
     * @return CheckoutLineItemInterface[]
     */
    public function getLineItems();
}
