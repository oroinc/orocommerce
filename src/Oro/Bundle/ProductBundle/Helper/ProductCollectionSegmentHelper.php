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
    /**
     * @var ContentVariantSegmentProvider
     */
    private $contentVariantSegmentProvider;

    /**
     * @var WebCatalogUsageProviderInterface
     */
    private $webCatalogUsageProvider;

    /**
     * @var array
     */
    private $websiteIdsByWebCatalog;

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
        $websiteIds = [];
        $contentVariants = $this->contentVariantSegmentProvider->getContentVariants($segment);

        foreach ($contentVariants as $contentVariant) {
            $websiteIds = array_merge(
                $websiteIds,
                $this->getWebsiteIdsByWebCatalog($contentVariant->getNode()->getWebCatalog())
            );
        }

        return $websiteIds;
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
