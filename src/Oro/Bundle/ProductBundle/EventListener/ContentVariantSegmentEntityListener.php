<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Component\WebCatalog\Provider\WebCatalogUsageProviderInterface;

/**
 * This listener is used to track segment entity's creation and definition change to schedule it for reindexation by
 * ContentVariantSegmentListener.
 */
class ContentVariantSegmentEntityListener
{
    /**
     * @var ContentVariantSegmentListener
     */
    private $contentVariantSegmentListener;

    /**
     * @var WebCatalogUsageProviderInterface
     */
    private $webCatalogUsageProvider;

    /**
     * @param ContentVariantSegmentListener $contentVariantSegmentListener
     * @param WebCatalogUsageProviderInterface|null $webCatalogUsageProvider
     */
    public function __construct(
        ContentVariantSegmentListener $contentVariantSegmentListener,
        WebCatalogUsageProviderInterface $webCatalogUsageProvider = null
    ) {
        $this->contentVariantSegmentListener = $contentVariantSegmentListener;
        $this->webCatalogUsageProvider = $webCatalogUsageProvider;
    }

    /**
     * @param Segment $segment
     */
    public function postPersist(Segment $segment)
    {
        if (!$this->webCatalogUsageProvider) {
            return;
        }

        $this->contentVariantSegmentListener->scheduleSegment($segment);
    }

    /**
     * @param Segment $segment
     * @param PreUpdateEventArgs $args
     */
    public function preUpdate(Segment $segment, PreUpdateEventArgs $args)
    {
        if (!$this->webCatalogUsageProvider) {
            return;
        }

        if ($args->hasChangedField('definition')) {
            $this->contentVariantSegmentListener->scheduleSegment($segment);
        }
    }
}
