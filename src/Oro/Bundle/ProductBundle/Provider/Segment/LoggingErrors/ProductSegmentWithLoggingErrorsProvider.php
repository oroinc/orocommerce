<?php

namespace Oro\Bundle\ProductBundle\Provider\Segment\LoggingErrors;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\Segment\ProductSegmentProviderInterface;
use Oro\Bundle\SegmentBundle\Entity\Manager\SegmentManager;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Psr\Log\LoggerInterface;

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
     * @param SegmentManager  $segmentManager
     * @param LoggerInterface $logger
     */
    public function __construct(SegmentManager $segmentManager, LoggerInterface $logger)
    {
        $this->segmentManager = $segmentManager;
        $this->logger = $logger;
    }

    /**
     * @param string $segmentId
     *
     * @return Segment|null
     */
    public function getProductSegmentById($segmentId)
    {
        $segment = $this->segmentManager->findById($segmentId);

        if (!$segment) {
            $this->logger->error('Segment was not found', ['id' => $segmentId]);

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
