<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\OrganizationBundle\Provider\OrganizationRestrictionProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SegmentBundle\Entity\Manager\SegmentManager;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Psr\Log\LoggerInterface;

/**
 * A service to get a segment contains a list of products by a segment ID.
 */
class ProductSegmentProvider
{
    private SegmentManager $segmentManager;
    private OrganizationRestrictionProviderInterface $organizationRestrictionProvider;
    private LoggerInterface $logger;

    public function __construct(
        SegmentManager $segmentManager,
        OrganizationRestrictionProviderInterface $organizationRestrictionProvider,
        LoggerInterface $logger,
    ) {
        $this->segmentManager = $segmentManager;
        $this->organizationRestrictionProvider = $organizationRestrictionProvider;
        $this->logger = $logger;
    }

    public function getProductSegmentById(int $segmentId): ?Segment
    {
        $segment = $this->segmentManager->findById($segmentId);
        if (null === $segment) {
            $this->logger->error('Segment was not found', ['id' => $segmentId]);

            return null;
        }

        if ($segment->getEntity() !== Product::class) {
            $this->logger->error(
                sprintf('Expected segment for "%s", but "%s" is given.', Product::class, $segment->getEntity()),
                [
                    'id'     => $segment->getId(),
                    'name'   => $segment->getName(),
                    'entity' => $segment->getEntity(),
                    'type'   => $segment->getType()->getName(),
                ]
            );

            return null;
        }

        if (!$this->organizationRestrictionProvider->isEnabledOrganization($segment->getOrganization())) {
            return null;
        }

        return $segment;
    }
}
