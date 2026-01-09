<?php

namespace Oro\Bundle\ProductBundle\RelatedItem\UpsellProduct;

use Oro\Bundle\ProductBundle\Entity\RelatedItem\UpsellProduct;
use Oro\Bundle\ProductBundle\RelatedItem\AbstractAssignerDatabaseStrategy;

/**
 * Database strategy for assigning upsell products to products.
 *
 * This strategy implements the database operations for creating and managing upsell product associations,
 * extending the abstract assigner with upsell product-specific entity and repository handling.
 */
class AssignerDatabaseStrategy extends AbstractAssignerDatabaseStrategy
{
    #[\Override]
    protected function createNewRelation()
    {
        return new UpsellProduct();
    }

    #[\Override]
    protected function getEntityManager()
    {
        return $this->doctrineHelper->getEntityManager(UpsellProduct::class);
    }

    #[\Override]
    protected function getRepository()
    {
        return $this->doctrineHelper->getEntityRepository(UpsellProduct::class);
    }
}
