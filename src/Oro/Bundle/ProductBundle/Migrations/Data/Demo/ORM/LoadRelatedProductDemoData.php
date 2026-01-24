<?php

namespace Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM;

use Oro\Bundle\ProductBundle\Entity\RelatedItem\RelatedProduct;

/**
 * Loads demo data for related products.
 *
 * This fixture creates sample related product associations from CSV data,
 * demonstrating the related products feature with realistic product relationships.
 */
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
