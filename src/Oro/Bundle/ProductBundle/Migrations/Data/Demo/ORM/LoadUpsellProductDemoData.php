<?php

namespace Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM;

use Oro\Bundle\ProductBundle\Entity\RelatedItem\UpsellProduct;

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
