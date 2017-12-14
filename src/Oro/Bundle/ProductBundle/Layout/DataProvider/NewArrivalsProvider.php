<?php

namespace Oro\Bundle\ProductBundle\Layout\DataProvider;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\UserBundle\Entity\AbstractUser;

class NewArrivalsProvider extends AbstractSegmentProductsProvider
{
    /**
     * {@inheritDoc}
     */
    public function getProducts()
    {
        return $this->applyMinItemsLimit(parent::getProducts());
    }

    /**
     * {@inheritdoc}
     */
    protected function getCacheParts(Segment $segment)
    {
        $user = $this->getTokenStorage()->getToken()->getUser();
        $userId = 0;
        if ($user instanceof AbstractUser) {
            $userId = $user->getId();
        }

        return ['new_arrivals_products', $userId, $segment->getId()];
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

            $this->setMaxItemsLimit($qb);

            if (!$this->isMinAndMaxLimitsValid()) {
                return null;
            }
        }

        return $qb;
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

        return $this->getConfigManager()->get($key);
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
}
