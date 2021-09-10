<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Stub;

use Oro\Bundle\ProductBundle\Helper\ProductCollectionSegmentHelper;
use Oro\Bundle\ProductBundle\Provider\ContentVariantSegmentProvider;
use Oro\Bundle\SegmentBundle\Entity\Segment;

class ProductCollectionSegmentHelperStub extends ProductCollectionSegmentHelper
{
    /** @var ProductCollectionSegmentHelper */
    private $helper;

    /** @var bool */
    private $isWebCatalogUsageProviderEnabled = true;

    public function __construct(
        ContentVariantSegmentProvider $contentVariantSegmentProvider,
        ProductCollectionSegmentHelper $helper
    ) {
        parent::__construct($contentVariantSegmentProvider);

        $this->helper = $helper;
    }

    public function setIsWebCatalogUsageProviderEnabled(bool $isWebCatalogUsageProviderEnabled): void
    {
        $this->isWebCatalogUsageProviderEnabled = $isWebCatalogUsageProviderEnabled;
    }

    /**
     * {@inheritdoc}
     */
    public function getWebsiteIdsBySegment(Segment $segment): array
    {
        return $this->isWebCatalogUsageProviderEnabled
            ? $this->helper->getWebsiteIdsBySegment($segment)
            : parent::getWebsiteIdsBySegment($segment);
    }

    /**
     * {@inheritdoc}
     */
    public function isEnabled(): bool
    {
        return $this->isWebCatalogUsageProviderEnabled ? $this->helper->isEnabled() : parent::isEnabled();
    }
}
