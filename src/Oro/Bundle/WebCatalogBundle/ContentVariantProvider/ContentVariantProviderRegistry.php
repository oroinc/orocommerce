<?php

namespace Oro\Bundle\WebCatalogBundle\ContentVariantProvider;

use Oro\Component\WebCatalog\ContentVariantProviderInterface;

class ContentVariantProviderRegistry
{
    /**
     * @var ContentVariantProviderInterface[]
     */
    protected $providers = [];
    
    /**
     * @param ContentVariantProviderInterface $provider
     */
    public function addProvider(ContentVariantProviderInterface $provider)
    {
        $this->providers[] = $provider;
    }

    /**
     * @return ContentVariantProviderInterface[]
     */
    public function getProviders()
    {
        return $this->providers;
    }
}
