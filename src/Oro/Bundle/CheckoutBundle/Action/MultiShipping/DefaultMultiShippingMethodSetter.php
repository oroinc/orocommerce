<?php

namespace Oro\Bundle\CheckoutBundle\Action\MultiShipping;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Manager\MultiShipping\CheckoutLineItemsShippingManager;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\DefaultMultipleShippingMethodProvider;
use Oro\Bundle\CheckoutBundle\Shipping\Method\CheckoutShippingMethodsProviderInterface;
use Symfony\Bridge\Doctrine\ManagerRegistry;

/**
 * Sets a default shipping method and a shipping cost for a checkout and its line items
 * when the multi shipping functionality is enabled.
 */
class DefaultMultiShippingMethodSetter
{
    private DefaultMultipleShippingMethodProvider $multiShippingMethodProvider;
    private CheckoutShippingMethodsProviderInterface $shippingPriceProvider;
    private ManagerRegistry $doctrine;
    private CheckoutLineItemsShippingManager $lineItemsShippingManager;

    public function __construct(
        DefaultMultipleShippingMethodProvider $multiShippingMethodProvider,
        CheckoutShippingMethodsProviderInterface $shippingPriceProvider,
        ManagerRegistry $doctrine,
        CheckoutLineItemsShippingManager $lineItemsShippingManager
    ) {
        $this->multiShippingMethodProvider = $multiShippingMethodProvider;
        $this->shippingPriceProvider = $shippingPriceProvider;
        $this->doctrine = $doctrine;
        $this->lineItemsShippingManager = $lineItemsShippingManager;
    }

    public function setDefaultShippingMethods(
        Checkout $checkout,
        array $lineItemsShippingMethods = [],
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

        $this->doctrine->getManagerForClass(CheckoutLineItem::class)->flush();
    }
}
