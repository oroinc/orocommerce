<?php

namespace Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM;

use Oro\Bundle\ProductBundle\Entity\RelatedItem\RelatedProduct;

class LoadRelatedProductDemoData extends AbstractLoadRelatedItemDemoData
{
    #[\Override]
    protected function getModel()
    {
        return new RelatedProduct();
    }

    #[\Override]
    protected function getFixtures()
    {
        return '@OroProductBundle/Migrations/Data/Demo/ORM/data/related_products.csv';
    }
}
