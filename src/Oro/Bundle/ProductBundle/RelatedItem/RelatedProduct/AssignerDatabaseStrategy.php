<?php

namespace Oro\Bundle\ProductBundle\RelatedItem\RelatedProduct;

use Oro\Bundle\ProductBundle\Entity\RelatedItem\RelatedProduct;
use Oro\Bundle\ProductBundle\RelatedItem\AbstractAssignerDatabaseStrategy;

/**
 * Database strategy for assigning related products to products.
 *
 * This strategy implements the database operations for creating and managing related product associations,
 * extending the abstract assigner with related product-specific entity and repository handling.
 */
class AssignerDatabaseStrategy extends AbstractAssignerDatabaseStrategy
{
    #[\Override]
    protected function createNewRelation()
    {
        return new RelatedProduct();
    }

    #[\Override]
    protected function getEntityManager()
    {
        return $this->doctrineHelper->getEntityManager(RelatedProduct::class);
    }

    #[\Override]
    protected function getRepository()
    {
        return $this->doctrineHelper->getEntityRepository(RelatedProduct::class);
    }
}
