<?php

namespace Oro\Bundle\CheckoutBundle\DataProvider\LineItem;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutProductKitItemLineItem;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\ProductLineItemPrice;
use Oro\Bundle\PricingBundle\ProductKit\ProductLineItemPrice\ProductKitLineItemPrice;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemPriceProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\VisibilityBundle\Provider\ResolvedProductVisibilityProvider;
use Oro\Component\Checkout\DataProvider\AbstractCheckoutProvider;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Provides info to build collection of line items by given source entity.
 * Source entity should implement ProductLineItemsHolderInterface.
 */
class CheckoutLineItemsDataProvider extends AbstractCheckoutProvider
{
    private ProductLineItemPriceProviderInterface $productLineItemPriceProvider;
    private AuthorizationCheckerInterface $authorizationChecker;
    private CacheInterface $productAvailabilityCache;
    private ResolvedProductVisibilityProvider $resolvedProductVisibilityProvider;

    public function __construct(
        ProductLineItemPriceProviderInterface $productLineItemPriceProvider,
        AuthorizationCheckerInterface $authorizationChecker,
        CacheInterface $productAvailabilityCache,
        ResolvedProductVisibilityProvider $resolvedProductVisibilityProvider
    ) {
        $this->productLineItemPriceProvider = $productLineItemPriceProvider;
        $this->authorizationChecker = $authorizationChecker;
        $this->productAvailabilityCache = $productAvailabilityCache;
        $this->resolvedProductVisibilityProvider = $resolvedProductVisibilityProvider;
    }

    /**
     * @param Checkout $entity
     *
     * @return array<int|string,array<string,mixed>> Line items data
     *  [
     *      [
     *          'product' => Product $product,
     *          'productUnit' => ProductUnit $productUnit,
     *          'quantity' => float 12.3456,
     *          // ...
     *      ],
     *      // ...
     *  ]
     */
    protected function prepareData($entity): array
    {
        $lineItems = $entity->getLineItems();
        $this->prefetchProductsVisibility($lineItems);

        $skippedLineItems = [];
        $productLineItemPrices = $this->getLineItemsPrices($lineItems, $skippedLineItems);

        $lineItemsData = [];

        /** @var CheckoutLineItem $lineItem */
        foreach ($lineItems as $key => $lineItem) {
            if (isset($skippedLineItems[$key])) {
                continue;
            }

            $kitItemLineItemsData = [];
            $productLineItemPrice = $productLineItemPrices[$key] ?? null;

            foreach ($lineItem->getKitItemLineItems() as $kitItemLineItem) {
                $kitItemLineItemsData[] = [
                    'kitItem' => $kitItemLineItem->getKitItem(),
                    'product' => $kitItemLineItem->getProduct(),
                    'productSku' => $kitItemLineItem->getProductSku(),
                    'unit' => $kitItemLineItem->getProductUnit(),
                    'productUnitCode' => $kitItemLineItem->getProductUnitCode(),
                    'quantity' => $kitItemLineItem->getQuantity(),
                    'price' => $this->getKitItemLineItemPrice($kitItemLineItem, $productLineItemPrice),
                    'sortOrder' => $kitItemLineItem->getSortOrder(),
                ];
            }

            $lineItemsData[] = [
                'productSku' => $lineItem->getProductSku(),
                'comment' => $lineItem->getComment(),
                'quantity' => $lineItem->getQuantity(),
                'productUnit' => $lineItem->getProductUnit(),
                'productUnitCode' => $lineItem->getProductUnitCode(),
                'product' => $lineItem->getProduct(),
                'parentProduct' => $lineItem->getParentProduct(),
                'freeFormProduct' => $lineItem->getFreeFormProduct(),
                'fromExternalSource' => $lineItem->isFromExternalSource(),
                'price' => $lineItem->getPrice() ?: $productLineItemPrice?->getPrice(),
                'shippingMethod' => $lineItem->getShippingMethod(),
                'shippingMethodType' => $lineItem->getShippingMethodType(),
                'shippingEstimateAmount' => $lineItem->getShippingEstimateAmount(),
                'checksum' => $lineItem->getChecksum(),
                'kitItemLineItems' => $kitItemLineItemsData,
            ];
        }

        return $lineItemsData;
    }

    /**
     * @param Collection<CheckoutLineItem> $lineItems
     *
     * @return array<int,ProductLineItemPrice>
     */
    private function getLineItemsPrices(Collection $lineItems, array &$skippedLineItems): array
    {
        $lineItemsWithoutFixedPrice = [];
        foreach ($lineItems as $key => $lineItem) {
            if (!$this->isLineItemNeeded($lineItem)) {
                $skippedLineItems[$key] = $lineItem;
                continue;
            }

            if ($lineItem->getProduct() && !$lineItem->isPriceFixed() && !$lineItem->getPrice()) {
                $lineItemsWithoutFixedPrice[$key] = $lineItem;
            }
        }

        if (!$lineItemsWithoutFixedPrice) {
            return [];
        }

        return $this->productLineItemPriceProvider->getProductLineItemsPrices($lineItemsWithoutFixedPrice);
    }

    private function getKitItemLineItemPrice(
        CheckoutProductKitItemLineItem $lineItem,
        ?ProductLineItemPrice $productLineItemPrice
    ): ?Price {
        if ($lineItem->getPrice() !== null) {
            return $lineItem->getPrice();
        }

        if ($productLineItemPrice instanceof ProductKitLineItemPrice) {
            return $productLineItemPrice->getKitItemLineItemPrice($lineItem)?->getPrice();
        }

        return null;
    }

    private function prefetchProductsVisibility(Collection $lineItems): void
    {
        $productIds = [];
        foreach ($lineItems as $lineItem) {
            if ($lineItem instanceof ProductHolderInterface && $lineItem->getProduct()) {
                $productIds[] = $lineItem->getProduct()->getId();
            }
        }

        if ($productIds) {
            $this->resolvedProductVisibilityProvider->prefetch(array_unique($productIds));
        }
    }

    public function isEntitySupported($transformData): bool
    {
        return $transformData instanceof Checkout;
    }

    /**
     * Is Line Item should be included in the results of data preparation
     */
    protected function isLineItemNeeded(CheckoutLineItem $lineItem): bool
    {
        $product = $lineItem->getProduct();
        if (!$product) {
            return true;
        }

        return $this->productAvailabilityCache->get((string)$product->getId(), function () use ($product) {
            return $product->getStatus() === Product::STATUS_ENABLED
                && $this->authorizationChecker->isGranted('VIEW', $product);
        });
    }
}
