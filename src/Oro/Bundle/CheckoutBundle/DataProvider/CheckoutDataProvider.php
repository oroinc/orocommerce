<?php

namespace Oro\Bundle\CheckoutBundle\DataProvider;

use Doctrine\Common\Collections\Collection;
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
use Oro\Component\Checkout\DataProvider\AbstractCheckoutProvider;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Provides info to build collection of line items by given source entity.
 * Source entity should implement ProductLineItemsHolderInterface.
 */
class CheckoutDataProvider extends AbstractCheckoutProvider
{
    private ProductLineItemPriceProviderInterface $productLineItemPriceProvider;
    private AuthorizationCheckerInterface $authorizationChecker;
    private CacheInterface $productAvailabilityCache;
    private ResolvedProductVisibilityProvider $resolvedProductVisibilityProvider;
    private CheckoutValidationGroupsBySourceEntityProvider $validationGroupsProvider;
    private ValidatorInterface $validator;

    /** @var array<string|array<string>>  */
    private array $validationGroups = [['Default', 'checkout_line_items_data']];

    public function __construct(
        ProductLineItemPriceProviderInterface $productLineItemPriceProvider,
        AuthorizationCheckerInterface $authorizationChecker,
        CacheInterface $productAvailabilityCache,
        ResolvedProductVisibilityProvider $resolvedProductVisibilityProvider,
        CheckoutValidationGroupsBySourceEntityProvider $checkoutValidationGroupsBySourceEntityProvider,
        ValidatorInterface $validator
    ) {
        $this->productLineItemPriceProvider = $productLineItemPriceProvider;
        $this->authorizationChecker = $authorizationChecker;
        $this->productAvailabilityCache = $productAvailabilityCache;
        $this->resolvedProductVisibilityProvider = $resolvedProductVisibilityProvider;
        $this->validationGroupsProvider = $checkoutValidationGroupsBySourceEntityProvider;
        $this->validator = $validator;
    }

    /**
     * @param array<string> $validationGroups
     */
    public function setValidationGroups(array $validationGroups): void
    {
        $this->validationGroups = $validationGroups;
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
        $lineItemsWithoutFixedPrice = [];
        foreach ($lineItems as $index => $lineItem) {
            if (isset($invalidLineItems[$index])) {
                continue;
            }

            if (!$this->isLineItemNeeded($lineItem)) {
                $invalidLineItems[$index] = $lineItem;
                continue;
            }

            if ($lineItem->getProduct() && !$lineItem->isPriceFixed() && !$lineItem->getPrice()) {
                $lineItemsWithoutFixedPrice[$index] = $lineItem;
            }
        }

        if (!$lineItemsWithoutFixedPrice) {
            return [];
        }

        return $this->productLineItemPriceProvider->getProductLineItemsPrices($lineItemsWithoutFixedPrice);
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
            return $this->authorizationChecker->isGranted('VIEW', $product);
        });
    }
}
