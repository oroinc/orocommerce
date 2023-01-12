<?php

namespace Oro\Bundle\CheckoutBundle\Manager\MultiShipping;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutLineItemsProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\LineItem\AvailableLineItemShippingMethodsProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\LineItem\LineItemShippingPriceProviderInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

/**
 * Implements logic to handle line items shipping data actions.
 */
class CheckoutLineItemsShippingManager
{
    public const SHIPPING_METHOD_FIELD = 'method';
    public const SHIPPING_METHOD_TYPE_FIELD = 'type';

    private AvailableLineItemShippingMethodsProvider $lineItemShippingMethodsProvider;
    private CheckoutLineItemsProvider $lineItemsProvider;
    private LineItemShippingPriceProviderInterface $shippingPricePriceProvider;

    public function __construct(
        AvailableLineItemShippingMethodsProvider $lineItemShippingMethodsProvider,
        CheckoutLineItemsProvider $lineItemsProvider,
        LineItemShippingPriceProviderInterface $shippingPricePriceProvider
    ) {
        $this->lineItemShippingMethodsProvider = $lineItemShippingMethodsProvider;
        $this->lineItemsProvider = $lineItemsProvider;
        $this->shippingPricePriceProvider = $shippingPricePriceProvider;
    }

    /**
     * Update line items shipping methods from provided data.
     *
     * @param array $shippingData ['2BV:item' => ['method' => 'flat_rate_1', 'type' => 'primary'], ... ]
     * @param Checkout $checkout
     * @param bool $useDefaults
     */
    public function updateLineItemsShippingMethods(
        array $shippingData,
        Checkout $checkout,
        bool $useDefaults = false
    ): void {
        $checkoutLineItems = $this->lineItemsProvider->getCheckoutLineItems($checkout);
        foreach ($checkoutLineItems as $lineItem) {
            $lineItemIdentifier = $this->getLineItemIdentifier($lineItem);
            $lineItemShippingData = $shippingData[$lineItemIdentifier] ?? [];

            if ($useDefaults && empty($lineItemShippingData)) {
                $lineItemShippingData = $this->getDefaultLineItemShippingMethod($lineItem);
            }

            if (!empty($lineItemShippingData)) {
                $lineItem->setShippingMethod($lineItemShippingData[self::SHIPPING_METHOD_FIELD] ?? null);
                $lineItem->setShippingMethodType($lineItemShippingData[self::SHIPPING_METHOD_TYPE_FIELD] ?? null);
            }
        }
    }

    /**
     * Build lineItems shipping data.
     *
     * @param Checkout $checkout
     *
     * @return array ['2BV:item' => ['method' => 'flat_rate_1', 'type' => 'primary'], ... ]
     */
    public function getCheckoutLineItemsShippingData(Checkout $checkout): array
    {
        $lineItemsShippingData = [];
        $lineItems = $this->lineItemsProvider->getCheckoutLineItems($checkout);
        foreach ($lineItems as $lineItem) {
            $identifier = $this->getLineItemIdentifier($lineItem);
            $lineItemsShippingData[$identifier] = [
                self::SHIPPING_METHOD_FIELD => $lineItem->getShippingMethod(),
                self::SHIPPING_METHOD_TYPE_FIELD => $lineItem->getShippingMethodType()
            ];
        }

        return $lineItemsShippingData;
    }

    public function updateLineItemsShippingPrices(Checkout $checkout): void
    {
        $lineItems = $this->lineItemsProvider->getCheckoutLineItems($checkout);
        foreach ($lineItems as $lineItem) {
            if (!$lineItem->getShippingMethod()) {
                continue;
            }

            $lineItem->setShippingEstimateAmount(
                $this->shippingPricePriceProvider->getPrice($lineItem)?->getValue()
            );
        }
    }

    public function getLineItemIdentifier(ProductLineItemInterface $lineItem): string
    {
        return implode(':', [$lineItem->getProductSku(), $lineItem->getProductUnitCode()]);
    }

    private function getDefaultLineItemShippingMethod(CheckoutLineItem $lineItem): array
    {
        $shippingMethods = $this->lineItemShippingMethodsProvider->getAvailableShippingMethods($lineItem);

        if (!empty($shippingMethods)) {
            $defaultShippingMethod = reset($shippingMethods);
            $defaultShippingMethodType = isset($defaultShippingMethod['types'])
                ? reset($defaultShippingMethod['types'])
                : [];

            if ($defaultShippingMethodType) {
                return [
                    self::SHIPPING_METHOD_FIELD => $defaultShippingMethod['identifier'],
                    self::SHIPPING_METHOD_TYPE_FIELD => $defaultShippingMethodType['identifier']
                ];
            }
        }

        return [];
    }
}
