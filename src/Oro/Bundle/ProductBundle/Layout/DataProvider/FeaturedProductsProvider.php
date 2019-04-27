<?php

namespace Oro\Bundle\ProductBundle\Layout\DataProvider;

use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\UserBundle\Entity\AbstractUser;

/**
 * Provide featured products
 */
class FeaturedProductsProvider extends AbstractSegmentProductsProvider
{
    const FEATURED_PRODUCTS_CACHE_KEY = 'oro_product.layout.data_provider.featured_products_featured_products';

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

        return ['featured_products', $userId, $segment->getId(), $segment->getRecordsLimit()];
    }

    /**
     * {@inheritdoc}
     */
    protected function getSegmentId()
    {
        return $this->getConfigManager()
            ->get(sprintf('%s.%s', Configuration::ROOT_NODE, Configuration::FEATURED_PRODUCTS_SEGMENT_ID));
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
}
