<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Migrations\Schema\v1_10;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ShoppingListBundle\Entity\ProductKitItemLineItem;

/**
 * Adds "checksum" column for {@see ProductKitItemLineItem::$checksum} field.
 * Adds "checksum" column to unique index "oro_shopping_list_line_item_uidx".
 */
class AddOroShoppingListItemLineItemChecksumColumn implements Migration
{
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_shopping_list_line_item');
        if (!$table->hasColumn('checksum')) {
            $table->addColumn('checksum', 'string', ['length' => 40, 'notnull' => true, 'default' => '']);

            $this->addColumnToUniqueIndex($table);
        }
    }

    private function addColumnToUniqueIndex(Table $table): void
    {
        $indexColumns = ['product_id', 'shopping_list_id', 'unit_code', 'checksum'];
        $indexName = 'oro_shopping_list_line_item_uidx';
        if ($table->hasIndex($indexName)) {
            if ($table->getIndex($indexName)->getColumns() !== $indexColumns) {
                $table->dropIndex($indexName);
                $table->addUniqueIndex($indexColumns, $indexName);
            }
        } else {
            $table->addUniqueIndex($indexColumns, $indexName);
        }
    }
}
