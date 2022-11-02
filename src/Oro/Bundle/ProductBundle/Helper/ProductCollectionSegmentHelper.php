<?php

namespace Oro\Bundle\ProductBundle\Helper;

use Oro\Bundle\ProductBundle\Provider\ContentVariantSegmentProvider;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Component\WebCatalog\Entity\WebCatalogInterface;
use Oro\Component\WebCatalog\Provider\WebCatalogUsageProviderInterface;

/**
 * Helper service to get product collection relation information by segment
 */
class ProductCollectionSegmentHelper
{
    private ContentVariantSegmentProvider $contentVariantSegmentProvider;
    private ?WebCatalogUsageProviderInterface $webCatalogUsageProvider;
    private ?array $websiteIdsByWebCatalog = null;

    public function __construct(
        ContentVariantSegmentProvider $contentVariantSegmentProvider,
        WebCatalogUsageProviderInterface $webCatalogUsageProvider = null
    ) {
        $this->contentVariantSegmentProvider = $contentVariantSegmentProvider;
        $this->webCatalogUsageProvider = $webCatalogUsageProvider;
    }

    /**
     * Get website ids by segment.
     *
     * Website identifiers are collected by web catalog in which there are product collection
     * content variants assigned to given segment
     */
    public function getWebsiteIdsBySegment(Segment $segment): array
    {
        if (!$this->isEnabled()) {
            return [];
        }

        $this->websiteIdsByWebCatalog = null;
        $contentVariants = $this->contentVariantSegmentProvider->getContentVariants($segment);

        $websiteIdBatches = [];
        foreach ($contentVariants as $contentVariant) {
            $websiteIdBatches[] = $this->getWebsiteIdsByWebCatalog(
                $contentVariant->getNode()->getWebCatalog()
            );
        }

        return \array_unique(\array_merge(...$websiteIdBatches));
    }

    private function getWebsiteIdsByWebCatalog(WebCatalogInterface $webCatalog): array
    {
        if (null === $this->websiteIdsByWebCatalog) {
            $this->websiteIdsByWebCatalog = [];
            $assignedWebCatalogs = $this->webCatalogUsageProvider->getAssignedWebCatalogs();

            foreach ($assignedWebCatalogs as $websiteId => $webCatalogId) {
                $this->websiteIdsByWebCatalog[$webCatalogId][] = $websiteId;
            }
        }

        return $this->websiteIdsByWebCatalog[$webCatalog->getId()] ?? [];
    }

    public function isEnabled(): bool
    {
        return (bool) $this->webCatalogUsageProvider;
    }
}
