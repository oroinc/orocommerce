<?php

namespace Oro\Bundle\WebCatalogBundle\ContentVariantProvider;

use Oro\Component\WebCatalog\ContentVariantProviderInterface;

class ContentVariantProviderRegistry
{
    /**
     * @var ContentVariantProviderInterface[]
     */
    protected $contentVariants = [];
    
    /**
     * @param ContentVariantProviderInterface $contentVariant
     */
    public function addProvider(ContentVariantProviderInterface $contentVariant)
    {
        $this->contentVariants[] = $contentVariant;
    }

    /**
     * @return ContentVariantProviderInterface[]
     */
    public function getProviders()
    {
        return $this->contentVariants;
    }
}
