<?php

namespace Oro\Bundle\CheckoutBundle\Action\MultiShipping;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
use Oro\Bundle\CurrencyBundle\Entity\Price;

/**
 * Sets a shipping method and a shipping cost for a child checkout
 * when Multi Shipping functionality is enabled.
 */
class SubOrderMultiShippingMethodSetter
{
    private ConfigProvider $configProvider;

    public function __construct(ConfigProvider $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    public function setShippingMethod(Checkout $checkout, Checkout $childCheckout, string $lineItemGroupKey): void
    {
        if (!$this->configProvider->isShippingSelectionByLineItemEnabled()) {
            $lineItemGroupShippingData = $checkout->getLineItemGroupShippingData();
            $shippingData = $lineItemGroupShippingData[$lineItemGroupKey] ?? null;
            if ($shippingData) {
                $shippingMethod = $shippingData['method'] ?? null;
                if ($shippingMethod) {
                    $childCheckout->setShippingMethod($shippingMethod);
                    $childCheckout->setShippingMethodType($shippingData['type']);
                    $shippingAmount = $shippingData['amount'] ?? null;
                    $childCheckout->setShippingCost(
                        null !== $shippingAmount
                            ? Price::create($shippingAmount, $childCheckout->getCurrency())
                            : null
                    );
                }
            }
        } elseif ($childCheckout->getLineItemGroupShippingData()) {
            $childCheckout->setLineItemGroupShippingData([]);
        }
    }
}
