<?php

namespace Oro\Component\WebCatalog;

use Doctrine\Common\Collections\Collection;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;

/**
 * Indicates that content variant creates new entity.
 */
interface ContentVariantEntityProviderInterface
{
    /**
     * @param ContentVariantInterface $contentVariant
     * @return object|Collection
     */
    public function getAttachedEntity(ContentVariantInterface $contentVariant);
}
