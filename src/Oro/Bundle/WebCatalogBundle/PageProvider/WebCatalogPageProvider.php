<?php

namespace Oro\Bundle\WebCatalogBundle\PageProvider;

use Oro\Component\WebCatalog\Entity\ContentVariantInterface;

class WebCatalogPageProvider
{
    /**
     * @var WebCatalogPageProviderRegistry
     */
    protected $providerRegistry;

    /**
     * @param WebCatalogPageProviderRegistry $providerRegistry
     */
    public function __construct(WebCatalogPageProviderRegistry $providerRegistry)
    {
        $this->providerRegistry = $providerRegistry;
    }
    
    /**
     * @param string $className
     * @return bool
     */
    public function isSupportedClass($className)
    {
        foreach ($this->providerRegistry->getProviders() as $provider) {
            if ($provider->isSupportedClass($className)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param object $entity
     * @return ContentVariantInterface[]
     */
    public function getContentVariantsByEntity($entity)
    {
        $result = [];
        foreach ($this->providerRegistry->getProviders() as $provider) {
            $pages = $provider->getContentVariantsByEntity($entity);
            if ($pages) {
                $result = array_merge($result, $pages);
            }
        }
        
        return $result;
    }

    /**
     * @param object[] $entities
     * @return ContentVariantInterface[]
     */
    public function getContentVariantsByEntities(array $entities)
    {
        $result = [];
        foreach ($this->providerRegistry->getProviders() as $provider) {
            $contentVariantsByEntities = $provider->getContentVariantsByEntities($entities);
            foreach ($contentVariantsByEntities as $entityId => $contentVariants) {
                $result[$entityId] = isset($result[$entityId])
                    ? array_merge($result[$entityId], $contentVariants)
                    : $contentVariants;
            }
        }

        return $result;
    }
}
