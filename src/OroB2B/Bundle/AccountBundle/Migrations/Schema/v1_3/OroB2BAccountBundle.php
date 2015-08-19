<?php

namespace OroB2B\Bundle\AccountBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class OroB2BAccountBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createOroB2BNavigationHistoryTable($schema);
        $this->createOroB2BNavigationItemTable($schema);
        $this->createOroB2BNavigationItemPinbarTable($schema);
        $this->addOroB2BNavigationHistoryForeignKeys($schema);
        $this->addOroB2BNavigationItemForeignKeys($schema);
        $this->addOroB2BNavigationItemPinbarForeignKeys($schema);
        $this->createOrob2BAccountUserSdbarStTable($schema);
        $this->createOrob2BAccountUserSdbarWdgTable($schema);
        $this->addOrob2BAccountUserSdbarStForeignKeys($schema);
        $this->addOrob2BAccountUserSdbarWdgForeignKeys($schema);
    }

    /**
     * Create orob2b_acc_navigation_history table
     *
     * @param Schema $schema
     */
    protected function createOroB2BNavigationHistoryTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_acc_navigation_history');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_user_id', 'integer', []);
        $table->addColumn('url', 'string', ['length' => 1023]);
        $table->addColumn('title', 'text', []);
        $table->addColumn('visited_at', 'datetime', []);
        $table->addColumn('visit_count', 'integer', []);
        $table->addColumn('route', 'string', ['length' => 128]);
        $table->addColumn('route_parameters', 'array', ['comment' => '(DC2Type:array)']);
        $table->addColumn('entity_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['route'], 'orob2b_navigation_history_route_idx');
        $table->addIndex(['entity_id'], 'orob2b_navigation_history_entity_id_idx');
    }

    /**
     * Create orob2b_acc_navigation_item table
     *
     * @param Schema $schema
     */
    protected function createOroB2BNavigationItemTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_acc_navigation_item');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_user_id', 'integer', []);
        $table->addColumn('type', 'string', ['length' => 20]);
        $table->addColumn('url', 'string', ['length' => 1023]);
        $table->addColumn('title', 'text', []);
        $table->addColumn('position', 'smallint', []);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['account_user_id', 'position'], 'oro_b2b_sorted_items_idx', []);
    }

    /**
     * Create orob2b_acc_nav_item_pinbar table
     *
     * @param Schema $schema
     */
    protected function createOroB2BNavigationItemPinbarTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_acc_nav_item_pinbar');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('item_id', 'integer', []);
        $table->addColumn('maximized', 'datetime', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add orob2b_navigation_history foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BNavigationHistoryForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_navigation_history');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account_user'),
            ['account_user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_navigation_item foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BNavigationItemForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_navigation_item');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account_user'),
            ['account_user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_acc_nav_item_pinbar foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BNavigationItemPinbarForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_acc_nav_item_pinbar');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_acc_navigation_item'),
            ['item_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Create orob2b_account_user_sdbar_st table
     *
     * @param Schema $schema
     */
    protected function createOrob2BAccountUserSdbarStTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_account_user_sdbar_st');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('account_user_id', 'integer', []);
        $table->addColumn('position', 'string', ['length' => 13]);
        $table->addColumn('state', 'string', ['length' => 17]);
        $table->addUniqueIndex(['account_user_id', 'position'], 'b2b_sdbar_st_unq_idx');
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create orob2b_account_user_sdbar_wdg table
     *
     * @param Schema $schema
     */
    protected function createOrob2BAccountUserSdbarWdgTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_account_user_sdbar_wdg');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_user_id', 'integer', []);
        $table->addColumn('placement', 'string', ['length' => 50]);
        $table->addColumn('position', 'smallint', []);
        $table->addColumn('widget_name', 'string', ['length' => 50]);
        $table->addColumn('settings', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->addColumn('state', 'string', ['length' => 22]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['position'], 'b2b_sdar_wdgs_pos_idx', []);
        $table->addIndex(['account_user_id', 'placement'], 'b2b_sdbr_wdgs_usr_place_idx', []);
    }

    /**
     * Add orob2b_account_user_sdbar_st foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BAccountUserSdbarStForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_account_user_sdbar_st');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account_user'),
            ['account_user_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add orob2b_account_user_sdbar_wdg foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BAccountUserSdbarWdgForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_account_user_sdbar_wdg');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account_user'),
            ['account_user_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}
