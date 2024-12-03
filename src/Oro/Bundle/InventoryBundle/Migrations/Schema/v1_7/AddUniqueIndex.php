<?php

namespace Oro\Bundle\InventoryBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Remove duplicates from inventory levels and add unique index on rows
 */
class AddUniqueIndex implements Migration
{
    public function getDescription(): string
    {
        return 'Remove duplicates from inventory levels and add unique index on rows';
    }

    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_inventory_level');
        $queries->addPreQuery($this->removeDuplicates($table));
        $this->addUniqueIndex($table);
    }

    private function addUniqueIndex(Table $table): void
    {
        $indexName = 'oro_inventory_level_unique_index';
        $indexColumns = $this->getIndexColumns($table);

        if (!$table->hasIndex($indexName)) {
            $table->addUniqueIndex(
                $indexColumns,
                $indexName
            );
        }
    }

    private function removeDuplicates(Table $table): string
    {
        $indexColumns = implode(", ", $this->getIndexColumns($table));

        return sprintf(
            'DELETE FROM %s WHERE id IN (' .
                'SELECT id FROM (' .
                    'SELECT id, ROW_NUMBER() OVER (' .
                        'PARTITION BY %s ' .
                        'ORDER BY id DESC' .
                    ') AS row_num ' .
                    'FROM oro_inventory_level' .
                ') AS duplicates ' .
                'WHERE row_num > 1' .
            ')',
            $table->getName(),
            $indexColumns,
        );
    }

    private function getIndexColumns(Table $table): array
    {
        $indexColumns = [
            'product_id',
            'product_unit_precision_id',
            'organization_id',
        ];

        if ($table->hasColumn('warehouse_id')) {
            $indexColumns[] = 'warehouse_id';
        }

        return $indexColumns;
    }
}
