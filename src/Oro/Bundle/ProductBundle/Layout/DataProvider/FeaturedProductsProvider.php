<?php

namespace Oro\Bundle\ProductBundle\Layout\DataProvider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\SegmentBundle\Entity\Manager\SegmentManager;
use Oro\Bundle\SegmentBundle\Entity\Segment;

class FeaturedProductsProvider
{
    /** @var SegmentManager */
    private $segmentManager;

    /** @var ProductManager */
    private $productManager;

    /** @var ConfigManager */
    private $configManager;

    /**
     * @param SegmentManager $segmentManager
     * @param ProductManager $productManager
     * @param ConfigManager  $configManager
     */
    public function __construct(
        SegmentManager $segmentManager,
        ProductManager $productManager,
        ConfigManager $configManager
    ) {
        $this->segmentManager = $segmentManager;
        $this->productManager = $productManager;
        $this->configManager = $configManager;
    }

    /**
     * @return array
     */
    public function getAll()
    {
        $segmentId = $this->configManager
            ->get(sprintf('%s.%s', Configuration::ROOT_NODE, Configuration::FEATURED_PRODUCTS_SEGMENT_ID));
        if (!$segmentId) {
            return [];
        }

        /** @var Segment $segment */
        $segment = $this->segmentManager->findById($segmentId);
        if ($segment !== null) {
            $qb = $this->segmentManager->getEntityQueryBuilder($segment);
            if ($qb !== null) {
                return $this->productManager->restrictQueryBuilder($qb, [])->getQuery()->getResult();
            }
        }

        return [];
    }
}
