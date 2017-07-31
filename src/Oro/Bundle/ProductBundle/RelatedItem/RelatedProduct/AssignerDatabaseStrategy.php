<?php

namespace Oro\Bundle\ProductBundle\RelatedItem\RelatedProduct;

use Oro\Bundle\ProductBundle\Entity\RelatedItem\RelatedProduct;
use Oro\Bundle\ProductBundle\RelatedItem\AbstractAssignerDatabaseStrategy;

class AssignerDatabaseStrategy extends AbstractAssignerDatabaseStrategy
{
    /**
     * {@inheritDoc}
     */
    protected function createNewRelation()
    {
        return new RelatedProduct();
    }

    /**
     * {@inheritDoc}
     */
    protected function getEntityManager()
    {
        return $this->doctrineHelper->getEntityManager(RelatedProduct::class);
    }

    /**
     * {@inheritDoc}
     */
    protected function getRepository()
    {
        return $this->doctrineHelper->getEntityRepository(RelatedProduct::class);
    }
}
