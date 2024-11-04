<?php

namespace Oro\Bundle\ProductBundle\Migrations\Data\ORM;

use Oro\Bundle\EntityExtendBundle\Migration\Fixture\AbstractEnumFixture;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 *  Loads product inventory status enum options
 */
class LoadProductInventoryStatusData extends AbstractEnumFixture
{
    #[\Override]
    protected function getData(): array
    {
        return [
            Product::INVENTORY_STATUS_IN_STOCK => 'In Stock',
            Product::INVENTORY_STATUS_OUT_OF_STOCK => 'Out of Stock',
            Product::INVENTORY_STATUS_DISCONTINUED => 'Discontinued'
        ];
    }

    #[\Override]
    protected function getEnumCode(): string
    {
        return 'prod_inventory_status';
    }

    #[\Override]
    protected function getDefaultValue(): string
    {
        return Product::INVENTORY_STATUS_IN_STOCK;
    }
}
