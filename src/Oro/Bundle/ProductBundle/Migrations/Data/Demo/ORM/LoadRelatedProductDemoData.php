<?php

namespace Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM;

use Oro\Bundle\ProductBundle\Entity\RelatedItem\RelatedProduct;

class LoadRelatedProductDemoData extends AbstractLoadRelatedItemDemoData
{
    /**
     * {@inheritDoc}
     */
    protected function getModel()
    {
        return new RelatedProduct();
    }

    /**
     * {@inheritDoc}
     */
    protected function getFixtures()
    {
        return '@OroProductBundle/Migrations/Data/Demo/ORM/data/related_products.csv';
    }
}
