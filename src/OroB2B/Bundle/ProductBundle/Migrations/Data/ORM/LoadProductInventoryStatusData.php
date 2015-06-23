<?php

namespace OroB2B\Bundle\ProductBundle\Migrations\Data\ORM;

use Oro\Bundle\EntityExtendBundle\Migration\Fixture\AbstractEnumFixture;

use OroB2B\Bundle\ProductBundle\Entity\Product;

class LoadProductInventoryStatusData extends AbstractEnumFixture
{
    /**
     * {@inheritdoc}
     */
    protected function getData()
    {
        return [
            Product::INVENTORY_STATUS_IN_STOCK     => 'In Stock',
            Product::INVENTORY_STATUS_OUT_OF_STOCK => 'Out of Stock',
            Product::INVENTORY_STATUS_DISCONTINUED => 'Discontinued'
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getEnumCode()
    {
        return 'prod_inventory_status';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultValue()
    {
        return Product::INVENTORY_STATUS_IN_STOCK;
    }
}
