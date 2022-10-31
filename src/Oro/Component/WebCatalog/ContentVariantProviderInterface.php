<?php

namespace Oro\Component\WebCatalog;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\QueryBuilder;
use Oro\Component\WebCatalog\Entity\ContentNodeInterface;

/**
 * Interface for the providers of each type of ContentVariants in WebCatalog
 */
interface ContentVariantProviderInterface
{
    /**
     * @param string $className
     * @return bool
     */
    public function isSupportedClass($className);

    /**
     * @param QueryBuilder $queryBuilder
     * @param string $entityClass
     * @param object[] $entities
     */
    public function modifyNodeQueryBuilderByEntities(QueryBuilder $queryBuilder, $entityClass, array $entities);

    /**
     * @param ContentNodeInterface $node
     * @return string[]
     */
    public function getValues(ContentNodeInterface $node);

    /**
     * @param ContentNodeInterface $node
     * @return Collection[]
     */
    public function getLocalizedValues(ContentNodeInterface $node);

    /**
     * @param array $item
     * @return mixed
     */
    public function getRecordId(array $item);

    /**
     * @param array $item
     * @return mixed
     */
    public function getRecordSortOrder(array $item);
}
