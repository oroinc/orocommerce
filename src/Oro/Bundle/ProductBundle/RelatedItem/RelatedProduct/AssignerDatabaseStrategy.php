<?php

namespace Oro\Bundle\ProductBundle\RelatedItem\RelatedProduct;

use Oro\Bundle\ProductBundle\Entity\RelatedItem\RelatedProduct;
use Oro\Bundle\ProductBundle\RelatedItem\AbstractAssignerDatabaseStrategy;

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
