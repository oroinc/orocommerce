<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_24;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Makes specified fields available for export on storefront.
 */
class EnableFieldsForExport implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        foreach (['sku', 'names', 'inventory_status'] as $fieldName) {
            $queries->addPostQuery(
                new UpdateEntityConfigFieldValueQuery(Product::class, $fieldName, 'frontend', 'use_in_export', true)
            );
        }
    }
}
