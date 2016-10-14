<?php

namespace Oro\Component\WebCatalog;

use Oro\Component\WebCatalog\Entity\ContentVariantInterface;

interface PageProviderInterface
{
    /**
     * @param string $className
     * @return bool
     */
    public function isSupportedClass($className);

    /**
     * @param object $entity
     * @return ContentVariantInterface[]
     */
    public function getPagesByEntity($entity);

    /**
     * @param object[] $entities
     * @return ContentVariantInterface[]
     */
    public function getPagesByEntities(array $entities);
}
