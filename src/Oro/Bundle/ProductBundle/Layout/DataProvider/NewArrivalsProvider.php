<?php

namespace Oro\Bundle\ProductBundle\Layout\DataProvider;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\Segment\ProductSegmentProviderInterface;
use Oro\Bundle\SegmentBundle\Entity\Manager\SegmentManager;

class NewArrivalsProvider
{
    /**
     * @var SegmentManager
     */
    private $segmentManager;

    /**
     * @var ProductSegmentProviderInterface
     */
    private $productSegmentProvider;

    /**
     * @var ProductManager
     */
    private $productManager;

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @param SegmentManager                  $segmentManager
     * @param ProductSegmentProviderInterface $productSegmentProvider
     * @param ProductManager                  $productManager
     * @param ConfigManager                   $configManager
     */
    public function __construct(
        SegmentManager $segmentManager,
        ProductSegmentProviderInterface $productSegmentProvider,
        ProductManager $productManager,
        ConfigManager $configManager
    ) {
        $this->segmentManager = $segmentManager;
        $this->productSegmentProvider = $productSegmentProvider;
        $this->productManager = $productManager;
        $this->configManager = $configManager;
    }

    /**
     * @return Product[]
     */
    public function getNewArrivals()
    {
        if (!$this->isMinAndMaxLimitsValid() || !$this->getSegmentId()) {
            return [];
        }

        $segment = $this->productSegmentProvider->getProductSegmentById($this->getSegmentId());

        if (!$segment) {
            return [];
        }

        $qb = $this->segmentManager->getEntityQueryBuilder($segment);

        if (!$qb) {
            return [];
        }

        $qb = $this->restrictByProductVisibility($qb);

        $this->setMaxItemsLimit($qb);

        $products = $qb->getQuery()->getResult();

        return $this->applyMinItemsLimit($products);
    }

    /**
     * @return bool
     */
    private function isMinAndMaxLimitsValid()
    {
        // if max limit is null, it is mean that there are no max limit
        $maxLimit = $this->getMaxItemsLimit();

        // if min limit is null, then we can operate it like zero
        $minLimit = (int)$this->getMinItemsLimit();

        return $maxLimit === null
            || ($maxLimit > 0 && $minLimit <= $maxLimit);
    }

    /**
     * @return bool
     */
    public function isUseSliderOnMobile()
    {
        return (bool)$this->getValueFromConfig(Configuration::NEW_ARRIVALS_USE_SLIDER_ON_MOBILE);
    }

    /**
     * @param QueryBuilder $qb
     *
     * @return QueryBuilder
     */
    private function restrictByProductVisibility(QueryBuilder $qb)
    {
        return $this->productManager->restrictQueryBuilder($qb, []);
    }

    /**
     * @param QueryBuilder $qb
     */
    private function setMaxItemsLimit(QueryBuilder $qb)
    {
        if (is_int($this->getMaxItemsLimit())) {
            $qb->setMaxResults($this->getMaxItemsLimit());
        }
    }

    /**
     * @param Product[] $products
     *
     * @return Product[]
     */
    private function applyMinItemsLimit(array $products)
    {
        if (count($products) < $this->getMinItemsLimit()) {
            return [];
        }

        return $products;
    }

    /**
     * @return string|null
     */
    private function getSegmentId()
    {
        return $this->getValueFromConfig(Configuration::NEW_ARRIVALS_PRODUCT_SEGMENT_ID);
    }

    /**
     * @return int|null
     */
    private function getMaxItemsLimit()
    {
        return $this->getValueFromConfig(Configuration::NEW_ARRIVALS_MAX_ITEMS);
    }

    /**
     * @return int|null
     */
    private function getMinItemsLimit()
    {
        return $this->getValueFromConfig(Configuration::NEW_ARRIVALS_MIN_ITEMS);
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    private function getValueFromConfig($key)
    {
        $key = Configuration::getConfigKeyByName($key);

        return $this->configManager->get($key);
    }
}
