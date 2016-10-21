<?php

namespace Oro\Component\WebCatalog;

use Oro\Component\WebCatalog\Entity\ContentVariantInterface;

interface PageProviderInterface
{
    /**
     * @return string
     */
    public function getName();
    
    /**
     * @param string $className
     * @return bool
     */
    public function isSupportedClass($className);

    /**
     * @param object $entity
     * @return ContentVariantInterface[]
     */
    public function getContentVariantsByEntity($entity);

    /**
     * @param object[] $entities
     * @return array
     * [
     *    ENTITY_ID => [contentVariant, ...]
     * ]
     */
    public function getContentVariantsByEntities(array $entities);
}
