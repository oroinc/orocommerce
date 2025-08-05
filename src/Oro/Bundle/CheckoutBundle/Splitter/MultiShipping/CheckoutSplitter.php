<?php

namespace Oro\Bundle\CheckoutBundle\Splitter\MultiShipping;

use Oro\Bundle\CheckoutBundle\Action\MultiShipping\SubOrderMultiShippingMethodSetter;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\MultiShipping\CheckoutFactoryInterface;

/**
 * Splits checkout by line items groups.
 */
class CheckoutSplitter
{
    private CheckoutFactoryInterface $checkoutFactory;
    private ?SubOrderMultiShippingMethodSetter $subOrderMultiShippingMethodSetter = null;

    public function __construct(CheckoutFactoryInterface $checkoutFactory)
    {
        $this->checkoutFactory = $checkoutFactory;
    }

    public function setSubOrderShippingMethodSetter(
        SubOrderMultiShippingMethodSetter $subOrderMultiShippingMethodSetter
    ): self {
        $this->subOrderMultiShippingMethodSetter = $subOrderMultiShippingMethodSetter;

        return $this;
    }

    /**
     * @param Checkout $checkout
     * @param array    $groupedLineItems
     *
     * @return Checkout[] ['product.owner:1' => checkout, ...]
     */
    public function split(Checkout $checkout, array $groupedLineItems): array
    {
        $groupedCheckouts = [];
        foreach ($groupedLineItems as $lineItemGroupKey => $lineItems) {
            $subCheckout = $this->checkoutFactory->createCheckout($checkout, $lineItems);
            $this->subOrderMultiShippingMethodSetter->setShippingMethod($checkout, $subCheckout, $lineItemGroupKey);
            $groupedCheckouts[$lineItemGroupKey] = $subCheckout;
        }

        return $groupedCheckouts;
    }
}
