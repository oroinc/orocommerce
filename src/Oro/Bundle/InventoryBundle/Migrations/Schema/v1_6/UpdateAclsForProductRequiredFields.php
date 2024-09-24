<?php

namespace Oro\Bundle\InventoryBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Mandatory product fields cannot be restricted at the creation page, as this will not allow to create a product.
 * Deny access to product creation if one of these fields needs to be restricted.
 */
class UpdateAclsForProductRequiredFields implements Migration
{
    private const PRODUCT_REQUIRED_FIELDS = [
        'manageInventory',
        'highlightLowInventory',
        'backOrder',
        'isUpcoming'
    ];

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        foreach (self::PRODUCT_REQUIRED_FIELDS as $field) {
            $queries->addQuery(new UpdateEntityConfigFieldValueQuery(
                Product::class,
                $field,
                'security',
                'permissions',
                'VIEW;EDIT'
            ));
        }
    }
}
