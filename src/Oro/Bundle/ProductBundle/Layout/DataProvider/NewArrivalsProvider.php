<?php

namespace Oro\Bundle\ProductBundle\Layout\DataProvider;

use Doctrine\ORM\Query;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\UserBundle\Entity\AbstractUser;

/**
 * Provide new arrivals products
 */
class NewArrivalsProvider extends AbstractSegmentProductsProvider
{
    /**
     * {@inheritDoc}
     */
    public function getProducts()
    {
        if (!$this->isMinAndMaxLimitsValid()) {
            return [];
        }

        return $this->applyMinItemsLimit(parent::getProducts());
    }

    /**
     * {@inheritdoc}
     */
    protected function getCacheParts(Segment $segment)
    {
        $user = $this->getTokenStorage()->getToken()->getUser();
        $website = $this->getWebsiteManager()->getCurrentWebsite();

        $userId = $user instanceof AbstractUser ? $user->getId() : 0;
        $websiteId = $website ? $website->getId() : 0;

        return ['new_arrivals_products', $userId, $websiteId, $segment->getId(), $segment->getRecordsLimit()];
    }

    /**
     * {@inheritdoc}
     */
    protected function getSegmentId()
    {
        return $this->getValueFromConfig(Configuration::NEW_ARRIVALS_PRODUCT_SEGMENT_ID);
    }

    /**
     * {@inheritdoc}
     */
    protected function getQueryBuilder(Segment $segment)
    {
        $qb = $this->getSegmentManager()->getEntityQueryBuilder($segment);

        if ($qb) {
            $qb = $this->getProductManager()->restrictQueryBuilder($qb, []);
        }

        return $qb;
    }

    /**
     * @return int|null
     */
    protected function getMaxItemsLimit()
    {
        return $this->getValueFromConfig(Configuration::NEW_ARRIVALS_MAX_ITEMS);
    }

    /**
     * @return int|null
     */
    protected function getMinItemsLimit()
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

        return $this->getConfigManager()->get($key);
    }

    /**
     * @param Query $query
     */
    private function setMaxItemsLimit($query)
    {
        if (is_int($this->getMaxItemsLimit())) {
            $query->setMaxResults($this->getMaxItemsLimit());
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
     * @return bool
     */
    private function isMinAndMaxLimitsValid()
    {
        // if max limit is null, it is mean that there are no max limit
        $maxLimit = $this->getMaxItemsLimit();

        // if min limit is null, then we can operate it like zero
        $minLimit = (int)$this->getMinItemsLimit();

        return $maxLimit === null || ($maxLimit > 0 && $minLimit <= $maxLimit);
    }

    /**
     * {@inheritdoc}
     */
    protected function restoreQuery(array $data)
    {
        $query = parent::restoreQuery($data);
        $this->setMaxItemsLimit($query);

        return $query;
    }
}
