<?php

namespace Oro\Component\WebCatalog;

use Oro\Component\WebCatalog\Entity\ContentVariantInterface;

interface ContentVariantTitleProviderInterface
{
    /**
     * Returns the content variant's title
     *
     * @param ContentVariantInterface $contentVariant
     *
     * @return string|null
     */
    public function getTitle(ContentVariantInterface $contentVariant);
}
