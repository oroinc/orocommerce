<?php

namespace Oro\Bundle\ProductBundle\RelatedItem\UpsellProduct;

use Oro\Bundle\ProductBundle\Entity\RelatedItem\UpsellProduct;
use Oro\Bundle\ProductBundle\RelatedItem\AbstractAssignerDatabaseStrategy;

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
