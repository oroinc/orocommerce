<?php

namespace Oro\Bundle\CheckoutBundle\Action\MultiShipping;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Manager\MultiShipping\CheckoutLineItemGroupsShippingManager;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\DefaultMultipleShippingMethodProvider;
use Oro\Bundle\CheckoutBundle\Shipping\Method\CheckoutShippingMethodsProviderInterface;
use Symfony\Bridge\Doctrine\ManagerRegistry;

/**
 * Sets a default shipping method and a shipping cost for a checkout and its line item groups
 * when Multi Shipping Per Line Item Groups functionality is enabled.
 */
class DefaultMultiShippingGroupMethodSetter implements DefaultMultiShippingGroupMethodSetterInterface
{
    private DefaultMultipleShippingMethodProvider $multiShippingMethodProvider;
    private CheckoutShippingMethodsProviderInterface $shippingPriceProvider;
    private ManagerRegistry $doctrine;
    private CheckoutLineItemGroupsShippingManager $lineItemGroupsShippingManager;

    public function __construct(
        DefaultMultipleShippingMethodProvider $multiShippingMethodProvider,
        CheckoutShippingMethodsProviderInterface $shippingPriceProvider,
        ManagerRegistry $doctrine,
        CheckoutLineItemGroupsShippingManager $lineItemGroupsShippingManager
    ) {
        $this->multiShippingMethodProvider = $multiShippingMethodProvider;
        $this->shippingPriceProvider = $shippingPriceProvider;
        $this->doctrine = $doctrine;
        $this->lineItemGroupsShippingManager = $lineItemGroupsShippingManager;
    }

    public function setDefaultShippingMethods(
        Checkout $checkout,
        ?array $lineItemGroupsShippingMethods = null,
        bool $useDefaults = false
    ): void {
        $multiShippingMethod = $this->multiShippingMethodProvider->getShippingMethod();
        $methodTypes = $multiShippingMethod->getTypes();
        $multiShippingMethodType = reset($methodTypes);

        $checkout->setShippingMethod($multiShippingMethod->getIdentifier());
        $checkout->setShippingMethodType($multiShippingMethodType->getIdentifier());

        $this->lineItemGroupsShippingManager->updateLineItemGroupsShippingMethods(
            $lineItemGroupsShippingMethods,
            $checkout,
            $useDefaults
        );
        $this->lineItemGroupsShippingManager->updateLineItemGroupsShippingPrices($checkout);

        $price = $this->shippingPriceProvider->getPrice($checkout);
        if (null !== $price) {
            $checkout->setShippingCost($price);
        }

        $this->doctrine->getManagerForClass(Checkout::class)->flush();
    }
}
