<?php

namespace Oro\Bundle\CheckoutBundle\Provider\MultiShipping\LineItem;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Factory\MultiShipping\CheckoutFactoryInterface;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\DefaultMultipleShippingMethodProvider;
use Oro\Bundle\CheckoutBundle\Shipping\Method\CheckoutShippingMethodsProviderInterface;

/**
 * Implement logic to get available shipping methods for single line item.
 */
class AvailableLineItemShippingMethodsProvider implements LineItemShippingMethodsProviderInterface
{
    private CheckoutShippingMethodsProviderInterface $shippingMethodsProvider;
    private DefaultMultipleShippingMethodProvider $multipleShippingMethodsProvider;
    private CheckoutFactoryInterface $checkoutFactory;
    private ?array $multipleShippingMethods = null;
    private array $cachedLineItemsShippingMethods = [];

    public function __construct(
        CheckoutShippingMethodsProviderInterface $shippingMethodsProvider,
        DefaultMultipleShippingMethodProvider $multipleShippingMethodsProvider,
        CheckoutFactoryInterface $checkoutFactory
    ) {
        $this->shippingMethodsProvider = $shippingMethodsProvider;
        $this->multipleShippingMethodsProvider = $multipleShippingMethodsProvider;
        $this->checkoutFactory = $checkoutFactory;
    }

    public function getAvailableShippingMethods(CheckoutLineItem $lineItem): array
    {
        $identifier = $lineItem->getId();

        if (!isset($this->cachedLineItemsShippingMethods[$identifier])) {
            $checkoutSource = $lineItem->getCheckout();

            //Create the same checkout but with single line item to obtain available shipping method.
            $shippingCheckout = $this->checkoutFactory->createCheckout($checkoutSource, [$lineItem]);
            $availableShippingMethods = $this->shippingMethodsProvider
                ->getApplicableMethodsViews($shippingCheckout)->toArray();

            if ($this->multipleShippingMethodsProvider->hasShippingMethods()) {
                $availableShippingMethods = $this->filterMultipleShippingMethods($availableShippingMethods);
            }

            $this->cachedLineItemsShippingMethods[$identifier] = $availableShippingMethods;
        }

        return $this->cachedLineItemsShippingMethods[$identifier];
    }

    /**
     * Configured multi_shipping method should not be available for line items. It should be set for checkout entity
     * only.
     *
     * @param array $shippingMethods
     * @return array
     */
    private function filterMultipleShippingMethods(array $shippingMethods): array
    {
        $multipleShippingMethods = $this->getMultipleShippingMethods();
        return array_filter(
            $shippingMethods,
            fn ($identifier) => !in_array($identifier, $multipleShippingMethods),
            ARRAY_FILTER_USE_KEY
        );
    }

    private function getMultipleShippingMethods(): array
    {
        if (null === $this->multipleShippingMethods) {
            $this->multipleShippingMethods = $this->multipleShippingMethodsProvider->getShippingMethods();
        }

        return $this->multipleShippingMethods;
    }
}
