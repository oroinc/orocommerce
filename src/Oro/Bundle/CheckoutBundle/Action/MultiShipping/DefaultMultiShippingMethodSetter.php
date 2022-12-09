<?php

namespace Oro\Bundle\CheckoutBundle\Action\MultiShipping;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Manager\MultiShipping\CheckoutLineItemsShippingManager;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\DefaultMultipleShippingMethodProvider;
use Oro\Bundle\CheckoutBundle\Shipping\Method\CheckoutShippingMethodsProviderInterface;
use Symfony\Bridge\Doctrine\ManagerRegistry;

/**
 * Sets default shipping method for checkout if multi shipping functionality is enabled.
 */
class DefaultMultiShippingMethodSetter
{
    private DefaultMultipleShippingMethodProvider $shippingProvider;
    private CheckoutShippingMethodsProviderInterface $shippingPriceProvider;
    private ManagerRegistry $doctrine;
    private CheckoutLineItemsShippingManager $lineItemsShippingManager;

    public function __construct(
        DefaultMultipleShippingMethodProvider $shippingProvider,
        CheckoutShippingMethodsProviderInterface $shippingPriceProvider,
        ManagerRegistry $doctrine,
        CheckoutLineItemsShippingManager $lineItemsShippingManager
    ) {
        $this->shippingProvider = $shippingProvider;
        $this->shippingPriceProvider = $shippingPriceProvider;
        $this->doctrine = $doctrine;
        $this->lineItemsShippingManager = $lineItemsShippingManager;
    }

    /**
     * Sets default multiple shipping method to checkout and update line items shipping data.
     *
     * @param Checkout $checkout
     * @param array $lineItemsShippingMethods
     * @param bool $useDefaults
     */
    public function setDefaultShippingMethods(
        Checkout $checkout,
        array $lineItemsShippingMethods = [],
        bool $useDefaults = false
    ): void {
        $multiShippingMethod = $this->shippingProvider->getShippingMethod();
        $methodTypes = $multiShippingMethod->getTypes();
        $multiShippingMethodType = reset($methodTypes);

        $checkout->setShippingMethod($multiShippingMethod->getIdentifier())
            ->setShippingMethodType($multiShippingMethodType->getIdentifier());

        /**
         * Update line items shipping data.
         */
        $this->lineItemsShippingManager
            ->updateLineItemsShippingMethods($lineItemsShippingMethods, $checkout, $useDefaults);
        $this->lineItemsShippingManager->updateLineItemsShippingPrices($checkout);

        $price = $this->shippingPriceProvider->getPrice($checkout);
        if ($price) {
            $checkout->setShippingCost($price);
        }

        $this->doctrine->getManagerForClass(CheckoutLineItem::class)
            ->flush();
    }
}
