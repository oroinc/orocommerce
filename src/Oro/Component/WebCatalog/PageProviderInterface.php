<?php

namespace Oro\Component\WebCatalog;

use Oro\Component\WebCatalog\Entity\WebCatalogPageInterface;

interface PageProviderInterface
{
    /**
     * @param object $className
     * @return bool
     */
    public function isSupportedClass($className);

    /**
     * @param object $entity
     * @return WebCatalogPageInterface[]
     */
    public function getPagesByEntity($entity);

    /**
     * @param object[] $entities
     * @return WebCatalogPageInterface[]
     */
    public function getPagesByEntities(array $entities);
}
