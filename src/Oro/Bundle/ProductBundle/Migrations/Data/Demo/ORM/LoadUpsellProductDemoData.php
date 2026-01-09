<?php

namespace Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM;

use Oro\Bundle\ProductBundle\Entity\RelatedItem\UpsellProduct;

/**
 * Loads demo data for upsell products.
 *
 * This fixture creates sample upsell product associations from CSV data,
 * demonstrating the upsell products feature with realistic product relationships.
 */
class LoadUpsellProductDemoData extends AbstractLoadRelatedItemDemoData
{
    #[\Override]
    protected function getModel()
    {
        return new UpsellProduct();
    }

    #[\Override]
    protected function getFixtures()
    {
        return '@OroProductBundle/Migrations/Data/Demo/ORM/data/upsell_products.csv';
    }
}
