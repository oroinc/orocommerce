<?php

namespace Oro\Bundle\CheckoutBundle\DataProvider\LineItem;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\PricingBundle\Provider\FrontendProductPricesDataProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\VisibilityBundle\Provider\ResolvedProductVisibilityProvider;
use Oro\Component\Checkout\DataProvider\AbstractCheckoutProvider;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Provides info to build collection of line items by given source entity.
 * Source entity should implement ProductLineItemsHolderInterface.
 */
class CheckoutLineItemsDataProvider extends AbstractCheckoutProvider
{
    /**
     * @var FrontendProductPricesDataProvider
     */
    protected $frontendProductPricesDataProvider;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var CacheProvider
     */
    private $productAvailabilityCache;

    /**
     * @var ResolvedProductVisibilityProvider
     */
    private $resolvedProductVisibilityProvider;

    public function __construct(
        FrontendProductPricesDataProvider $frontendProductPricesDataProvider,
        AuthorizationCheckerInterface $authorizationChecker,
        CacheProvider $productAvailabilityCache,
        ResolvedProductVisibilityProvider $resolvedProductVisibilityProvider
    ) {
        $this->frontendProductPricesDataProvider = $frontendProductPricesDataProvider;
        $this->authorizationChecker = $authorizationChecker;
        $this->productAvailabilityCache = $productAvailabilityCache;
        $this->resolvedProductVisibilityProvider = $resolvedProductVisibilityProvider;
    }

    /**
     * @param Checkout $entity
     *
     * {@inheritDoc}
     */
    protected function prepareData($entity)
    {
        $lineItems = $entity->getLineItems();
        $lineItemsPrices = $this->getLineItemsPrices($lineItems);

        $this->prefetchProductsVisibility($lineItems);

        $data = [];

        /** @var CheckoutLineItem $lineItem */
        foreach ($lineItems as $lineItem) {
            $unitCode = $lineItem->getProductUnitCode();
            $product = $lineItem->getProduct();
            $productId = $product ? $product->getId() : null;

            if ($this->isLineItemNeeded($lineItem)) {
                $data[] = [
                    'productSku' => $lineItem->getProductSku(),
                    'comment' => $lineItem->getComment(),
                    'quantity' => $lineItem->getQuantity(),
                    'productUnit' => $lineItem->getProductUnit(),
                    'productUnitCode' => $unitCode,
                    'product' => $product,
                    'parentProduct' => $lineItem->getParentProduct(),
                    'freeFormProduct' => $lineItem->getFreeFormProduct(),
                    'fromExternalSource' => $lineItem->isFromExternalSource(),
                    'price' => $lineItem->getPrice() ?: ($lineItemsPrices[$productId][$unitCode] ?? null),
                ];
            }
        }

        return $data;
    }

    private function getLineItemsPrices(Collection $lineItems): array
    {
        $lineItemsWithoutPrice = [];
        foreach ($lineItems as $lineItem) {
            if ($lineItem instanceof ProductHolderInterface && $lineItem->getProduct()) {
                if (!$lineItem->isPriceFixed() && !$lineItem->getPrice()) {
                    $lineItemsWithoutPrice[] = $lineItem;
                }
            }
        }

        return $lineItemsWithoutPrice ? $this->frontendProductPricesDataProvider
            ->getProductsMatchedPrice($lineItemsWithoutPrice) : [];
    }

    private function prefetchProductsVisibility(Collection $lineItems): void
    {
        $productIds = [];
        foreach ($lineItems as $lineItem) {
            if ($lineItem instanceof ProductHolderInterface && $lineItem->getProduct()) {
                $productIds[] = $lineItem->getProduct()->getId();
            }
        }

        $this->resolvedProductVisibilityProvider->prefetch(array_unique($productIds));
    }

    /**
     * {@inheritDoc}
     */
    public function isEntitySupported($transformData)
    {
        return $transformData instanceof Checkout;
    }

    /**
     * Is Line Item should be included in the results of data preparation
     *
     * @param CheckoutLineItem $lineItem
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    protected function isLineItemNeeded($lineItem)
    {
        if (!$lineItem instanceof ProductHolderInterface) {
            return true;
        }

        $product = $lineItem->getProduct();
        if (!$product) {
            return true;
        }

        if ($this->productAvailabilityCache->contains($product->getId())) {
            return $this->productAvailabilityCache->fetch($product->getId());
        }

        $isAvailable = $product->getStatus() === Product::STATUS_ENABLED
            && $this->authorizationChecker->isGranted('VIEW', $product);

        $this->productAvailabilityCache->save($product->getId(), $isAvailable);

        return $isAvailable;
    }
}
