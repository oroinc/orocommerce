<?php

namespace Oro\Bundle\VisibilityBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class OroAccountBundle implements Migration
{
    const ORO_B2B_CATEGORY_VISIBILITY_RESOLVED = 'orob2b_ctgr_vsb_resolv';
    const ORO_B2B_ACCOUNT_GROUP_CATEGORY_VISIBILITY_RESOLVED = 'orob2b_acc_grp_ctgr_vsb_resolv';
    const ORO_B2B_ACCOUNT_CATEGORY_VISIBILITY_RESOLVED = 'orob2b_acc_ctgr_vsb_resolv';

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroCategoryVisibilityResolvedTable($schema);
        $this->createOroAccountGroupCategoryVisibilityResolvedTable($schema);
        $this->createOroAccountCategoryVisibilityResolvedTable($schema);
        $this->createOrob2BWindowsStateTable($schema);

        /** Foreign keys generation **/
        $this->addOroCategoryVisibilityResolvedForeignKeys($schema);
        $this->addOroAccountGroupCategoryVisibilityResolvedForeignKeys($schema);
        $this->addOroAccountCategoryVisibilityResolvedForeignKeys($schema);
        $this->addOrob2BWindowsStateForeignKeys($schema);

        $this->clearUnusedProcessDefinitions($queries);
    }

    /**
     * Create orob2b_ctgr_vsb_resolv table
     *
     * @param Schema $schema
     */
    protected function createOroCategoryVisibilityResolvedTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_B2B_CATEGORY_VISIBILITY_RESOLVED);
        $table->addColumn('source_category_visibility', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'smallint', ['notnull' => false]);
        $table->addColumn('source', 'smallint', ['notnull' => false]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['category_id']);
    }

    /**
     * Create orob2b_acc_grp_ctgr_vsb_resolv table
     *
     * @param Schema $schema
     */
    protected function createOroAccountGroupCategoryVisibilityResolvedTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_B2B_ACCOUNT_GROUP_CATEGORY_VISIBILITY_RESOLVED);
        $table->addColumn('account_group_id', 'integer', []);
        $table->addColumn('source_category_visibility', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'smallint', ['notnull' => false]);
        $table->addColumn('source', 'smallint', ['notnull' => false]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['account_group_id', 'category_id']);
    }

    /**
     * Create orob2b_acc_ctgr_vsb_resolv table
     *
     * @param Schema $schema
     */
    protected function createOroAccountCategoryVisibilityResolvedTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_B2B_ACCOUNT_CATEGORY_VISIBILITY_RESOLVED);
        $table->addColumn('account_id', 'integer', []);
        $table->addColumn('source_category_visibility', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'smallint', ['notnull' => false]);
        $table->addColumn('source', 'smallint', ['notnull' => false]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['account_id', 'category_id']);
    }

    /**
     * Create orob2b_windows_state table
     *
     * @param Schema $schema
     */
    protected function createOrob2BWindowsStateTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_windows_state');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('customer_user_id', 'integer', []);
        $table->addColumn('data', Type::JSON_ARRAY, ['comment' => '(DC2Type:json_array)']);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addIndex(['customer_user_id'], 'orob2b_windows_state_acu_idx', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add orob2b_ctgr_vsb_resolv foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCategoryVisibilityResolvedForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_CATEGORY_VISIBILITY_RESOLVED);
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_category_visibility'),
            ['source_category_visibility'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_catalog_category'),
            ['category_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_acc_grp_ctgr_vsb_resolv foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroAccountGroupCategoryVisibilityResolvedForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_ACCOUNT_GROUP_CATEGORY_VISIBILITY_RESOLVED);
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_acc_grp_ctgr_visibility'),
            ['source_category_visibility'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account_group'),
            ['account_group_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_catalog_category'),
            ['category_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_acc_ctgr_vsb_resolv foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroAccountCategoryVisibilityResolvedForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_ACCOUNT_CATEGORY_VISIBILITY_RESOLVED);
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_acc_category_visibility'),
            ['source_category_visibility'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account'),
            ['account_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_catalog_category'),
            ['category_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_windows_state foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BWindowsStateForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_windows_state');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account_user'),
            ['customer_user_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * @param QueryBag $queries
     */
    protected function clearUnusedProcessDefinitions(QueryBag $queries)
    {
        $removedProcessDefinitions = [
            'category_position_cache_clear',
            'account_group_changed_cache_clear',
            'category_visibility_cache_clear',
            'account_group_category_visibility_cache_clear',
            'account_category_visibility_cache_clear'
        ];
        foreach ($removedProcessDefinitions as $definition) {
            $queries->addQuery(sprintf("DELETE FROM oro_process_definition WHERE name='%s'", $definition));
        }
    }
}
