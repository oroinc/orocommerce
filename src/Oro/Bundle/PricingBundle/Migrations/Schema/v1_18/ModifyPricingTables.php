<?php

namespace Oro\Bundle\PricingBundle\Migrations\Schema\v1_18;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator;

/**
 * - add version column to product price to be able to track changes for mass operations like import
 * - add origin_price_id to combined product price to store connection between combined and origin price
 *   No foreign key added because prices may be stored in different tables with enabled sharding.
 */
class ModifyPricingTables implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $identifierGenerator = new DbIdentifierNameGenerator();

        $this->addVersionToPricingTable($schema->getTable('oro_price_product'), 'oro_price_version_idx');
        // Add version column to shards
        foreach ($schema->getTableNames() as $tableName) {
            if (preg_match('/oro_price_product_\d+/', $tableName)) {
                $table = $schema->getTable($tableName);
                $idxName = $identifierGenerator->generateIndexName(
                    $table->getName(),
                    ['price_list_id', 'version', 'product_id']
                );
                $this->addVersionToPricingTable($table, $idxName);
            }
        }

        $table = $schema->getTable('oro_price_product_combined');
        if (!$table->hasColumn('origin_price_id')) {
            $table->addColumn('origin_price_id', 'guid', ['notnull' => false]);
        }
    }

    private function addVersionToPricingTable(\Doctrine\DBAL\Schema\Table $table, string $idxName): void
    {
        if (!$table->hasColumn('version')) {
            $table->addColumn('version', 'integer', ['notnull' => false]);
            $table->addIndex(['price_list_id', 'version', 'product_id'], $idxName, []);
        }
    }
}
