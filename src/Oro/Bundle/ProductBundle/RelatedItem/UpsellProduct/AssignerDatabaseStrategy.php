<?php

namespace Oro\Bundle\ProductBundle\RelatedItem\UpsellProduct;

use Oro\Bundle\ProductBundle\Entity\RelatedItem\UpsellProduct;
use Oro\Bundle\ProductBundle\RelatedItem\AbstractAssignerDatabaseStrategy;

class AssignerDatabaseStrategy extends AbstractAssignerDatabaseStrategy
{
    /**
     * {@inheritDoc}
     */
    protected function createNewRelation()
    {
        return new UpsellProduct();
    }

    /**
     * {@inheritDoc}
     */
    protected function getEntityManager()
    {
        return $this->doctrineHelper->getEntityManager(UpsellProduct::class);
    }

    /**
     * {@inheritDoc}
     */
    protected function getRepository()
    {
        return $this->doctrineHelper->getEntityRepository(UpsellProduct::class);
    }
}
