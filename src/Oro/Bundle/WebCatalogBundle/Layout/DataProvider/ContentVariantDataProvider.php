<?php

namespace Oro\Bundle\WebCatalogBundle\Layout\DataProvider;

use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Provider\RequestWebContentVariantProvider;

/**
 * The provider for the current web content variant.
 */
class ContentVariantDataProvider
{
    /** @var RequestWebContentVariantProvider */
    private $requestWebContentVariantProvider;

    public function __construct(RequestWebContentVariantProvider $requestWebContentVariantProvider)
    {
        $this->requestWebContentVariantProvider = $requestWebContentVariantProvider;
    }

    /**
     * @return ContentVariant|null
     */
    public function getFromRequest()
    {
        return $this->requestWebContentVariantProvider->getContentVariant();
    }

    public function getContentVariantType(): ?string
    {
        $contentVariant = $this->getFromRequest();
        if ($contentVariant) {
            return $contentVariant->getType();
        }

        return null;
    }
}
