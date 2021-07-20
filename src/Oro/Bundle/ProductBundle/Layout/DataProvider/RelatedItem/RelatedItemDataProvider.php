<?php

namespace Oro\Bundle\ProductBundle\Layout\DataProvider\RelatedItem;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\Restriction\RestrictedProductRepository;
use Oro\Bundle\ProductBundle\RelatedItem\AbstractRelatedItemConfigProvider;
use Oro\Bundle\ProductBundle\RelatedItem\FinderStrategyInterface;
use Oro\Bundle\UIBundle\Provider\UserAgentProviderInterface;

/**
 * Provide info to build collection of related products by given product entity.
 */
class RelatedItemDataProvider implements RelatedItemDataProviderInterface
{
    /** @var FinderStrategyInterface */
    private $finderStrategy;

    /** @var AbstractRelatedItemConfigProvider */
    private $configProvider;

    /** @var RestrictedProductRepository */
    private $restrictedProductRepository;

    /** @var UserAgentProviderInterface */
    private $userAgentProvider;

    public function __construct(
        FinderStrategyInterface $finderStrategy,
        AbstractRelatedItemConfigProvider $configProvider,
        RestrictedProductRepository $restrictedProductRepository,
        UserAgentProviderInterface $userAgentProvider
    ) {
        $this->finderStrategy = $finderStrategy;
        $this->configProvider = $configProvider;
        $this->restrictedProductRepository = $restrictedProductRepository;
        $this->userAgentProvider = $userAgentProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getRelatedItems(Product $product)
    {
        $relatedProductIds = $this->finderStrategy
            ->findIds($product, $this->configProvider->isBidirectional(), $this->configProvider->getLimit());

        if (!$this->hasMoreThanRequiredMinimum($relatedProductIds)) {
            return [];
        }

        $restrictedProducts = $this->restrictedProductRepository->findProducts(
            $relatedProductIds,
            $this->configProvider->getMaximumItems()
        );

        if (!$this->hasMoreThanRequiredMinimum($restrictedProducts)) {
            return [];
        }

        return $restrictedProducts;
    }

    /**
     * {@inheritdoc}
     */
    public function isSliderEnabled()
    {
        return !$this->isMobile() || $this->isSliderEnabledOnMobile();
    }

    /**
     * {@inheritdoc}
     */
    public function isAddButtonVisible()
    {
        return $this->configProvider->isAddButtonVisible();
    }

    /**
     * @param array $productIds
     * @return bool
     */
    private function hasMoreThanRequiredMinimum($productIds)
    {
        return count($productIds) !== 0 && count($productIds) >= (int)$this->configProvider->getMinimumItems();
    }

    /**
     * @return bool|mixed
     */
    private function isMobile()
    {
        return $this->userAgentProvider->getUserAgent()->isMobile();
    }

    /**
     * @return bool
     */
    private function isSliderEnabledOnMobile()
    {
        return $this->configProvider->isSliderEnabledOnMobile();
    }
}
