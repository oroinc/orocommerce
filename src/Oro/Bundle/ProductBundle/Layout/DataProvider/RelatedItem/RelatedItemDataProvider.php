<?php

namespace Oro\Bundle\ProductBundle\Layout\DataProvider\RelatedItem;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\Restriction\RestrictedProductRepository;
use Oro\Bundle\ProductBundle\RelatedItem\AbstractRelatedItemConfigProvider;
use Oro\Bundle\ProductBundle\RelatedItem\FinderStrategyInterface;
use Oro\Bundle\UIBundle\Provider\UserAgentProviderInterface;

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

    /**
     * @param FinderStrategyInterface           $finderStrategy
     * @param AbstractRelatedItemConfigProvider $configProvider
     * @param RestrictedProductRepository       $restrictedProductRepository
     * @param UserAgentProviderInterface        $userAgentProvider
     */
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
        $isBidirectional = $this->configProvider->isBidirectional();
        $limit = $this->configProvider->getLimit();

        /** @var Product[] $relatedProducts */
        $relatedProducts = method_exists($this->finderStrategy, 'findIds')
            ? $this->finderStrategy->findIds($product, $isBidirectional, $limit)
            : $this->finderStrategy->find($product, $isBidirectional, $limit);

        if (!$this->hasMoreThanRequiredMinimum($relatedProducts)) {
            return [];
        }

        $restrictedProducts = $this->restrictedProductRepository->findProducts(
            $relatedProducts instanceof Collection ? $relatedProducts->toArray() : $relatedProducts,
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
     * @param Product[] $products
     * @return bool
     */
    private function hasMoreThanRequiredMinimum($products)
    {
        return count($products) !== 0 && count($products) >= (int)$this->configProvider->getMinimumItems();
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
