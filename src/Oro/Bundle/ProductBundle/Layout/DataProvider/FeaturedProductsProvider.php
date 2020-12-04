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
        $website = $this->getWebsiteManager()->getCurrentWebsite();

        $userId = $user instanceof AbstractUser ? $user->getId() : 0;
        $websiteId = $website ? $website->getId() : 0;

        return ['featured_products', $userId, $websiteId, $segment->getId(), $segment->getRecordsLimit()];
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
