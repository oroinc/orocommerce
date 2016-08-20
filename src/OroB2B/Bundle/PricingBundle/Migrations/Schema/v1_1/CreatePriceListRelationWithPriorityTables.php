<?php

namespace Oro\Bundle\PricingBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class CreatePriceListRelationWithPriorityTables implements Migration, OrderedMigrationInterface
{
    const TMP_RELATION_ACCOUNT_TABLE_NAME = 'orob2b_price_list_to_acc_tmp';
    const TMP_RELATION_WEBSITE_TABLE_NAME = 'orob2b_price_list_to_ws_tmp';

    /**
     * @var array
     */
    protected $relationTableNames = [
        'account_id' => 'orob2b_account',
        'account_group_id' => 'orob2b_account_group',
    ];

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->migrateToNewRelationTable(
            $schema,
            $queries,
            'orob2b_price_list_to_account',
            self::TMP_RELATION_ACCOUNT_TABLE_NAME,
            'account_id'
        );
        $this->migrateToNewRelationTable(
            $schema,
            $queries,
            'orob2b_price_list_to_c_group',
            'orob2b_price_list_to_acc_group',
            'account_group_id'
        );
        $this->migrateToNewRelationTable(
            $schema,
            $queries,
            'orob2b_price_list_to_website',
            self::TMP_RELATION_WEBSITE_TABLE_NAME,
            'website_id'
        );
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     * @param string $oldTableName
     * @param string $newTableName
     * @param string $fieldName
     */
    protected function migrateToNewRelationTable(
        Schema $schema,
        QueryBag $queries,
        $oldTableName,
        $newTableName,
        $fieldName
    ) {
        $this->dropTableForeignKeys($schema, $oldTableName);
        $this->createPriceListToRelationTable($schema, $newTableName, $fieldName);
        $queries->addPostQuery($this->createMigratingDataQuery($newTableName, $oldTableName, $fieldName));
    }

    /**
     * @param string $newTableName
     * @param string $oldTableName
     * @param string $fieldName
     * @return InsertSelectPriceListRelationTablesQuery
     */
    protected function createMigratingDataQuery($newTableName, $oldTableName, $fieldName)
    {
        return new InsertSelectPriceListRelationTablesQuery($newTableName, $oldTableName, $fieldName);
    }

    /**
     * @param Schema $schema
     * @param string $tableName
     */
    protected function dropTableForeignKeys(Schema $schema, $tableName)
    {
        $table = $schema->getTable($tableName);
        foreach (array_keys($table->getForeignKeys()) as $constraintName) {
            $table->removeForeignKey($constraintName);
        }
        foreach (array_diff(array_keys($table->getIndexes()), [$table->getPrimaryKey()->getName()]) as $indexName) {
            $table->dropIndex($indexName);
        }
    }

    /**
     * @param Schema $schema
     * @param string $tableName
     * @param string $fieldName
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function createPriceListToRelationTable(Schema $schema, $tableName, $fieldName)
    {
        $table = $schema->createTable($tableName);
        $table->addColumn('price_list_id', 'integer', []);
        $table->addColumn('website_id', 'integer', []);
        $table->addColumn('priority', 'integer', []);
        $primaryKey = ['price_list_id', 'website_id'];
        if ($fieldName !== 'website_id') {
            $table->addColumn($fieldName, 'integer', []);
            $table->addForeignKeyConstraint(
                $schema->getTable($this->relationTableNames[$fieldName]),
                [$fieldName],
                ['id'],
                ['onUpdate' => null, 'onDelete' => 'CASCADE']
            );
            // Set correct order for primary key columns
            array_unshift($primaryKey, $fieldName);
        }
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_list'),
            ['price_list_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_website'),
            ['website_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->setPrimaryKey($primaryKey);
    }
}
