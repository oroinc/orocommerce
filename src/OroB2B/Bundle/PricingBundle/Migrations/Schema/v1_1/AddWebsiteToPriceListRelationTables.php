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
     * @var array
     */
    protected $priceListForeignKeyConstraintNames = [
        'orob2b_price_list_to_account' => 'fk_orob2b_price_l_to_acc_pl',
        'orob2b_price_list_to_acc_gr' => 'fk_orob2b_price_l_to_a_gr_pl',
        'orob2b_price_list_to_website' => 'fk_orob2b_price_l_to_ws_pl',
    ];

    /**
     * @var array
     */
    protected $websiteForeignKeyConstraintNames = [
        'orob2b_price_list_to_account' => 'fk_orob2b_price_l_to_acc_ws',
        'orob2b_price_list_to_acc_gr' => 'fk_orob2b_price_l_to_a_gr_ws',
        'orob2b_price_list_to_website' => 'fk_orob2b_price_l_to_ws_ws',
    ];

    /**
     * @var array
     */
    protected $relationTableForeignKeyConstraintNames = [
        'account_id' => 'fk_orob2b_price_l_to_acc_acc',
        'account_group_id' => 'fk_orob2b_price_l_to_a_gr_a_gr',
    ];

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
        $oldTableName = 'orob2b_price_list_to_c_group';
        $newTableName = 'orob2b_price_list_to_acc_gr';
        $this->recreateRelationTableWithPriority($schema, $queries, $newTableName, $oldTableName, 'account_group_id');
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
     * @param string $fieldName
     */
    protected function addPriorityToRelationTable(Schema $schema, QueryBag $queries, $tableName, $fieldName)
    {
        $tmpTableName = $this->getTmpTableName($tableName);
        $this->renameExtension->renameTable($schema, $queries, $tableName, $tmpTableName);
        $this->recreateRelationTableWithPriority($schema, $queries, $tableName, $tmpTableName, $fieldName);
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     * @param string $newTableName
     * @param string $oldTableName
     * @param string $fieldName
     */
    protected function recreateRelationTableWithPriority(
        Schema $schema,
        QueryBag $queries,
        $newTableName,
        $oldTableName,
        $fieldName
    ) {
        $queries->addPostQuery($this->createPriceListToRelationTableQuery($schema, $newTableName, $fieldName));
        $queries->addPostQuery($this->createMigratingDataQuery($newTableName, $oldTableName, $fieldName));
        $queries->addPostQuery($this->createDropTableQuery($oldTableName));
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
     * @param string $fieldName
     * @return SqlMigrationQuery
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function createPriceListToRelationTableQuery(Schema $schema, $tableName, $fieldName)
    {
        $tableScheme = new Schema();
        $table = $tableScheme->createTable($tableName);
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
                ['onUpdate' => null, 'onDelete' => 'CASCADE'],
                $this->relationTableForeignKeyConstraintNames[$fieldName]
            );
            $primaryKey[] = $fieldName;
        }
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_list'),
            ['price_list_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE'],
            $this->priceListForeignKeyConstraintNames[$tableName]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_website'),
            ['website_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE'],
            $this->websiteForeignKeyConstraintNames[$tableName]
        );
        $table->setPrimaryKey($primaryKey);

        $comparator = new Comparator();
        $changes = $comparator->compare(new Schema(), $tableScheme)->toSql($this->platform);
        return new SqlMigrationQuery($changes);
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
