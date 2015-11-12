<?php

namespace OroB2B\Bundle\PricingBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\SqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;

class AddWebsiteToPriceListRelationTables implements
    Migration,
    DatabasePlatformAwareInterface,
    RenameExtensionAwareInterface
{
    /**
     * @var int
     */
    protected $defaultWebsiteId;

    /**
     * @var AbstractPlatform
     */
    protected $platform;

    /**
     * @var RenameExtension
     */
    protected $renameExtension;

    /**
     * {@inheritdoc}
     */
    public function setDatabasePlatform(AbstractPlatform $platform)
    {
        $this->platform = $platform;
    }

    /**
     * {@inheritdoc}
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addPriorityToOroB2BPriceListToAccount($schema, $queries);
        $this->addPriorityToOroB2BPriceListToAccountGroup($schema, $queries);
        $this->addPriorityToOroB2BPriceListToWebsite($schema, $queries);
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     */
    protected function addPriorityToOroB2BPriceListToAccount(Schema $schema, QueryBag $queries)
    {
        $this->addPriorityToRelationTable($schema, $queries, 'orob2b_price_list_to_account', 'account_id');
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     */
    protected function addPriorityToOroB2BPriceListToAccountGroup(Schema $schema, QueryBag $queries)
    {
        $field = 'account_group_id';
        $oldTableName = 'orob2b_price_list_to_c_group';
        $newTableName = 'orob2b_price_list_to_acc_gr';
        $this->recreateRelationTableWithPriority($schema, $queries, $newTableName, $oldTableName, $field);
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     */
    protected function addPriorityToOroB2BPriceListToWebsite(Schema $schema, QueryBag $queries)
    {
        $this->addPriorityToRelationTable($schema, $queries, 'orob2b_price_list_to_website', 'website_id');
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     * @param string $tableName
     * @param string|null $field
     */
    protected function addPriorityToRelationTable(Schema $schema, QueryBag $queries, $tableName, $field)
    {
        $tmpTableName = $this->getTmpTableName($tableName);
        $this->renameExtension->renameTable($schema, $queries, $tableName, $tmpTableName);
        $this->recreateRelationTableWithPriority($schema, $queries, $tableName, $tmpTableName, $field);
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     * @param string $newTableName
     * @param string $oldTableName
     * @param string|null $field
     */
    protected function recreateRelationTableWithPriority(
        Schema $schema,
        QueryBag $queries,
        $newTableName,
        $oldTableName,
        $field = null
    ) {
        $queries->addPostQuery($this->createPriceListToRelationTableQuery($schema, $newTableName, $field));
        $queries->addPostQuery($this->createMigratingDataQuery($newTableName, $oldTableName, $field));
        $queries->addPostQuery($this->createDropTableQuery($oldTableName));
    }

    /**
     * @param string $newTableName
     * @param string $oldTableName
     * @param string $field
     * @return InsertSelectPriceListRelationTablesQuery
     */
    protected function createMigratingDataQuery($newTableName, $oldTableName, $field)
    {
        return new InsertSelectPriceListRelationTablesQuery($newTableName, $oldTableName, $field);
    }

    /**
     * @param Schema $schema
     * @param string $tableName
     * @param string|null $field
     * @return SqlMigrationQuery
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function createPriceListToRelationTableQuery(Schema $schema, $tableName, $field)
    {
        $tableScheme = new Schema();
        $table = $tableScheme->createTable($tableName);
        $table->addColumn('price_list_id', 'integer', []);
        $table->addColumn('website_id', 'integer', []);
        $table->addColumn('priority', 'integer', []);
        $primaryKey = ['price_list_id', 'website_id'];
        if ($field !== 'website_id') {
            $table->addColumn($field, 'integer', []);
            $table->addForeignKeyConstraint(
                $schema->getTable($this->getTableNameByFieldName($field)),
                [$field],
                ['id'],
                ['onUpdate' => null, 'onDelete' => 'CASCADE'],
                $this->createForeignKeyConstraintName($tableName, $this->getTableNameByFieldName($field))
            );
            $primaryKey[] = $field;
        }
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_list'),
            ['price_list_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE'],
            $this->createForeignKeyConstraintName($tableName, 'orob2b_price_list')
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_website'),
            ['website_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE'],
            $this->createForeignKeyConstraintName($tableName, 'orob2b_website')
        );
        $table->setPrimaryKey($primaryKey);

        $comparator = new Comparator();
        $changes = $comparator->compare(new Schema(), $tableScheme)->toSql($this->platform);
        return new SqlMigrationQuery($changes);
    }

    /**
     * @param string $tableName
     * @param string $foreignTableName
     * @return string
     */
    protected function createForeignKeyConstraintName($tableName, $foreignTableName)
    {
        return strtoupper(implode('_', ['idx', $tableName, $foreignTableName]));
    }

    /**
     * @param string $field
     * @return string
     */
    protected function getTableNameByFieldName($field)
    {
        $tablesByFields = [
            'account_id' => 'orob2b_account',
            'account_group_id' => 'orob2b_account_group',
        ];

        return $tablesByFields[$field];
    }

    /**
     * @param string $tableName
     * @return string
     */
    protected function getTmpTableName($tableName)
    {
        return substr($tableName, 0, 25) . '_tmp';
    }

    /**
     * @param string $tableName
     * @return SqlMigrationQuery
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function createDropTableQuery($tableName)
    {
        return new SqlMigrationQuery('DROP TABLE ' . $tableName);
    }
}
