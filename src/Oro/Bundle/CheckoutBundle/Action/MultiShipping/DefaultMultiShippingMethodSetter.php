<?php

namespace Oro\Bundle\CheckoutBundle\Action\MultiShipping;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Manager\MultiShipping\CheckoutLineItemsShippingManagerInterface;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\DefaultMultipleShippingMethodProvider;
use Oro\Bundle\CheckoutBundle\Shipping\Method\CheckoutShippingMethodsProviderInterface;

/**
 * Sets a default shipping method and a shipping cost for a checkout and its line items
 * when Multi Shipping Per Line Items functionality is enabled.
 */
class DefaultMultiShippingMethodSetter implements DefaultMultiShippingMethodSetterInterface
{
    private DefaultMultipleShippingMethodProvider $multiShippingMethodProvider;
    private CheckoutShippingMethodsProviderInterface $shippingPriceProvider;
    private CheckoutLineItemsShippingManagerInterface $lineItemsShippingManager;

    public function __construct(
        DefaultMultipleShippingMethodProvider $multiShippingMethodProvider,
        CheckoutShippingMethodsProviderInterface $shippingPriceProvider,
        CheckoutLineItemsShippingManagerInterface $lineItemsShippingManager
    ) {
        $this->multiShippingMethodProvider = $multiShippingMethodProvider;
        $this->shippingPriceProvider = $shippingPriceProvider;
        $this->lineItemsShippingManager = $lineItemsShippingManager;
    }

    #[\Override]
    public function setDefaultShippingMethods(
        Checkout $checkout,
        ?array $lineItemsShippingMethods = null,
        bool $useDefaults = false
    ): void {
        $multiShippingMethod = $this->multiShippingMethodProvider->getShippingMethod();
        $methodTypes = $multiShippingMethod->getTypes();
        $multiShippingMethodType = reset($methodTypes);

        $checkout->setShippingMethod($multiShippingMethod->getIdentifier());
        $checkout->setShippingMethodType($multiShippingMethodType->getIdentifier());

        $this->lineItemsShippingManager->updateLineItemsShippingMethods(
            $lineItemsShippingMethods,
            $checkout,
            $useDefaults
        );
        $this->lineItemsShippingManager->updateLineItemsShippingPrices($checkout);

        $price = $this->shippingPriceProvider->getPrice($checkout);
        if (null !== $price) {
            $checkout->setShippingCost($price);
        }
    }
}
