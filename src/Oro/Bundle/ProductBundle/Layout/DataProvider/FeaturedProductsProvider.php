<?php

namespace Oro\Bundle\ProductBundle\Layout\DataProvider;

use Psr\Log\LoggerInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Entity\Product;
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

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param SegmentManager  $segmentManager
     * @param ProductManager  $productManager
     * @param ConfigManager   $configManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        SegmentManager $segmentManager,
        ProductManager $productManager,
        ConfigManager $configManager,
        LoggerInterface $logger
    ) {
        $this->segmentManager = $segmentManager;
        $this->productManager = $productManager;
        $this->configManager = $configManager;
        $this->logger = $logger;
    }

    /**
     * @return array
     */
    public function getAll()
    {
        $segment = $this->getSegment();
        if ($segment) {
            $qb = $this->segmentManager->getEntityQueryBuilder($segment);
            if ($qb) {
                return $this->productManager->restrictQueryBuilder($qb, [])->getQuery()->getResult();
            }
        }

        return [];
    }

    /**
     * @return Segment|null
     */
    private function getSegment()
    {
        $segmentId = $this->configManager
            ->get(sprintf('%s.%s', Configuration::ROOT_NODE, Configuration::FEATURED_PRODUCTS_SEGMENT_ID));
        if ($segmentId) {
            $segment = $this->segmentManager->findById($segmentId);
            if ($segment && $segment->getEntity() !== Product::class) {
                $this->logger->error(
                    sprintf('Expected "%s", but "%s" is given.', Product::class, $segment->getEntity()),
                    [
                        'id' => $segment->getId(),
                        'name' => $segment->getName(),
                        'entity' => $segment->getEntity(),
                        'type' => $segment->getType()->getName(),
                    ]
                );

                return null;
            }

            return $segment;
        }

        return null;
    }
}
