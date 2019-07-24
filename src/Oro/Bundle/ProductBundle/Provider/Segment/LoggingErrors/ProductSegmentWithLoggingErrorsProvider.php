<?php

namespace Oro\Bundle\ProductBundle\Provider\Segment\LoggingErrors;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\Segment\ProductSegmentProviderInterface;
use Oro\Bundle\SegmentBundle\Entity\Manager\SegmentManager;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Psr\Log\LoggerInterface;

/**
 * Provider search and verify presence of a segment
 */
class ProductSegmentWithLoggingErrorsProvider implements ProductSegmentProviderInterface
{
    /**
     * @var SegmentManager
     */
    private $segmentManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var WebsiteManager
     */
    private $websiteManager;

    /**
     * @param SegmentManager $segmentManager
     * @param LoggerInterface $logger
     * @param WebsiteManager $websiteManager
     */
    public function __construct(
        SegmentManager $segmentManager,
        LoggerInterface $logger,
        WebsiteManager $websiteManager
    ) {
        $this->segmentManager = $segmentManager;
        $this->logger = $logger;
        $this->websiteManager = $websiteManager;
    }

    /**
     * @param string $segmentId
     *
     * @return Segment|null
     */
    public function getProductSegmentById($segmentId)
    {
        $website = $this->websiteManager->getCurrentWebsite();
        $segment = $this->segmentManager->findById($segmentId);

        if (!$segment) {
            $this->logger->error('Segment was not found', ['id' => $segmentId]);

            return null;
        }

        if ($segment->getOrganization() !== $website->getOrganization()) {
            return null;
        }

        if (!$this->isSegmentOfProductEntity($segment)) {
            return null;
        }

        return $segment;
    }

    /**
     * @param Segment $segment
     *
     * @return bool
     */
    private function isSegmentOfProductEntity(Segment $segment)
    {
        if ($segment->getEntity() !== Product::class) {
            $this->logger->error(
                sprintf('Expected "%s", but "%s" is given.', Product::class, $segment->getEntity()),
                [
                    'id' => $segment->getId(),
                    'name' => $segment->getName(),
                    'entity' => $segment->getEntity(),
                    'type' => $segment->getType()->getName(),
                ]
            );

            return false;
        }

        return true;
    }
}
