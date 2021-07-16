<?php

namespace Oro\Bundle\ProductBundle\Provider\Segment\LoggingErrors;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\Segment\ProductSegmentProviderInterface;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SegmentBundle\Entity\Manager\SegmentManager;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Psr\Log\LoggerInterface;

/**
 * Provider search and verify presence of a segment
 */
class ProductSegmentWithLoggingErrorsProvider implements ProductSegmentProviderInterface
{
    /** @var SegmentManager */
    private $segmentManager;

    /** @var LoggerInterface */
    private $logger;

    /** @var TokenAccessorInterface */
    private $tokenAccessor;

    public function __construct(
        SegmentManager $segmentManager,
        LoggerInterface $logger,
        TokenAccessorInterface $tokenAccessor
    ) {
        $this->segmentManager = $segmentManager;
        $this->logger = $logger;
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * @param string $segmentId
     *
     * @return Segment|null
     */
    public function getProductSegmentById($segmentId)
    {
        $organization = $this->tokenAccessor->getOrganization();
        if (null === $organization) {
            return null;
        }

        $segment = $this->segmentManager->findById($segmentId);
        if (null === $segment) {
            $this->logger->error('Segment was not found', ['id' => $segmentId]);

            return null;
        }

        if ($segment->getOrganization()->getId() !== $organization->getId()) {
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
