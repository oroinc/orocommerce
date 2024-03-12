<?php

namespace Oro\Bundle\CheckoutBundle\DataProvider;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CacheBundle\Provider\MemoryCacheProviderInterface;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutProductKitItemLineItem;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutValidationGroupsBySourceEntityProvider;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\ProductLineItemPrice;
use Oro\Bundle\PricingBundle\ProductKit\ProductLineItemPrice\ProductKitLineItemPrice;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemPriceProviderInterface;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\VisibilityBundle\Provider\ResolvedProductVisibilityProvider;
use Oro\Component\Checkout\DataProvider\CheckoutDataProviderInterface;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Provides info to build collection of line items by the given Checkout entity.
 */
class CheckoutDataProvider implements CheckoutDataProviderInterface
{
    private ProductLineItemPriceProviderInterface $productLineItemPriceProvider;
    private AuthorizationCheckerInterface $authorizationChecker;
    private CacheInterface $productAvailabilityCache;
    private ResolvedProductVisibilityProvider $resolvedProductVisibilityProvider;
    private CheckoutValidationGroupsBySourceEntityProvider $validationGroupsProvider;
    private ValidatorInterface $validator;
    private MemoryCacheProviderInterface $memoryCacheProvider;
    /** @var array<string|array<string>>  */
    private array $validationGroups = [['Default', 'checkout_line_items_data']];

    public function __construct(
        ProductLineItemPriceProviderInterface $productLineItemPriceProvider,
        AuthorizationCheckerInterface $authorizationChecker,
        CacheInterface $productAvailabilityCache,
        ResolvedProductVisibilityProvider $resolvedProductVisibilityProvider,
        CheckoutValidationGroupsBySourceEntityProvider $checkoutValidationGroupsBySourceEntityProvider,
        ValidatorInterface $validator,
        MemoryCacheProviderInterface $memoryCacheProvider
    ) {
        $this->productLineItemPriceProvider = $productLineItemPriceProvider;
        $this->authorizationChecker = $authorizationChecker;
        $this->productAvailabilityCache = $productAvailabilityCache;
        $this->resolvedProductVisibilityProvider = $resolvedProductVisibilityProvider;
        $this->validationGroupsProvider = $checkoutValidationGroupsBySourceEntityProvider;
        $this->validator = $validator;
        $this->memoryCacheProvider = $memoryCacheProvider;
    }

    /**
     * @param array<string> $validationGroups
     */
    public function setValidationGroups(array $validationGroups): void
    {
        $this->validationGroups = $validationGroups;
    }

    /**
     * {@inheritDoc}
     */
    public function isEntitySupported(object $entity): bool
    {
        return $entity instanceof Checkout;
    }

    /**
     * {@inheritDoc}
     */
    public function getData(object $entity): array
    {
        return $this->memoryCacheProvider->get(
            ['checkout' => $entity],
            function () use ($entity) {
                return $this->prepareData($entity);
            }
        );
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
    protected function prepareData(Checkout $entity): array
    {
        $lineItems = $entity->getLineItems();
        $this->prefetchProductsVisibility($lineItems);

        $invalidLineItems = $this->getInvalidLineItems($entity);
        $productLineItemPrices = $this->getLineItemsPrices($lineItems, $invalidLineItems);

        $lineItemsData = [];

        /** @var CheckoutLineItem $lineItem */
        foreach ($lineItems as $key => $lineItem) {
            if (isset($invalidLineItems[$key])) {
                continue;
            }

            $kitItemLineItemsData = [];
            $productLineItemPrice = $productLineItemPrices[$key] ?? null;

            if ($lineItem->getProduct()?->isKit()) {
                foreach ($lineItem->getKitItemLineItems() as $kitItemLineItem) {
                    $kitItemLineItemsData[] = [
                        'kitItem' => $kitItemLineItem->getKitItem(),
                        'product' => $kitItemLineItem->getProduct(),
                        'productSku' => $kitItemLineItem->getProductSku(),
                        'productUnit' => $kitItemLineItem->getProductUnit(),
                        'productUnitCode' => $kitItemLineItem->getProductUnitCode(),
                        'quantity' => $kitItemLineItem->getQuantity(),
                        'price' => $this->getKitItemLineItemPrice($kitItemLineItem, $productLineItemPrice),
                        'sortOrder' => $kitItemLineItem->getSortOrder(),
                    ];
                }
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
     * @param array<int,CheckoutLineItem> $invalidLineItems
     *
     * @return array<int,ProductLineItemPrice>
     */
    private function getLineItemsPrices(Collection $lineItems, array &$invalidLineItems): array
    {
        $lineItemsToGetPrices = [];
        foreach ($lineItems as $index => $lineItem) {
            if (isset($invalidLineItems[$index])) {
                continue;
            }

            if (!$this->isLineItemNeeded($lineItem)) {
                $invalidLineItems[$index] = $lineItem;
                continue;
            }

            if ($lineItem->getProduct()
                && (
                    (!$lineItem->getPrice() && !$lineItem->isPriceFixed())
                    // We should get prices for Product Kits even if price is fixed,
                    // because we need prices for Kit Item Line Items
                    || $lineItem->getProduct()->isKit()
                )
            ) {
                $lineItemsToGetPrices[$index] = $lineItem;
            }
        }

        if (!$lineItemsToGetPrices) {
            return [];
        }

        return $this->productLineItemPriceProvider->getProductLineItemsPrices($lineItemsToGetPrices);
    }

    /**
     * @param Checkout $checkout
     *
     * @return array<int,CheckoutLineItem>
     */
    private function getInvalidLineItems(Checkout $checkout): array
    {
        $lineItems = $checkout->getLineItems();
        if (!$lineItems->count()) {
            return [];
        }

        $validationGroups = $this->validationGroupsProvider
            ->getValidationGroupsBySourceEntity($this->validationGroups, $checkout->getSourceEntity());

        $violationList = $this->validator->validate($lineItems, null, $validationGroups);
        $invalidLineItems = [];
        foreach ($violationList as $violation) {
            if (!$violation->getPropertyPath()) {
                continue;
            }

            $propertyPath = new PropertyPath($violation->getPropertyPath());
            if (!$propertyPath->isIndex(0)) {
                continue;
            }

            $index = $propertyPath->getElement(0);
            $invalidLineItems[$index] = $lineItems[$index];
        }

        return $invalidLineItems;
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

    /**
     * Checks whether the given line item should be included in the results of data preparation.
     */
    protected function isLineItemNeeded(CheckoutLineItem $lineItem): bool
    {
        $product = $lineItem->getProduct();
        if (!$product) {
            return true;
        }

        return $this->productAvailabilityCache->get((string)$product->getId(), function () use ($product) {
            return $this->authorizationChecker->isGranted('VIEW', $product);
        });
    }
}
