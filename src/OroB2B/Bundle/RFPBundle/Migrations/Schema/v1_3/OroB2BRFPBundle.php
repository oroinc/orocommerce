<?php

namespace OroB2B\Bundle\RFPBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BRFPBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Table Modification */
        $this->addDeletedAtColumn($schema);

        /** Tables generation **/
        $this->createOroRfpAssignedAccUsersTable($schema);
        $this->createOroRfpAssignedUsersTable($schema);

        /** Foreign keys generation **/
        $this->addOroRfpAssignedAccUsersForeignKeys($schema);
        $this->addOroRfpAssignedUsersForeignKeys($schema);
    }

    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function addDeletedAtColumn(Schema $schema)
    {
        $table = $schema->getTable('orob2b_rfp_request');
        $table->addColumn('deleted_at', 'datetime', ['notnull' => false, 'comment' => '(DC2Type:datetime)']);
    }

    /**
     * Create oro_rfp_assigned_acc_users table
     *
     * @param Schema $schema
     */
    protected function createOroRfpAssignedAccUsersTable(Schema $schema)
    {
        $table = $schema->createTable('oro_rfp_assigned_acc_users');
        $table->addColumn('quote_id', 'integer', []);
        $table->addColumn('account_user_id', 'integer', []);
        $table->setPrimaryKey(['quote_id', 'account_user_id']);
    }

    /**
     * Create oro_rfp_assigned_users table
     *
     * @param Schema $schema
     */
    protected function createOroRfpAssignedUsersTable(Schema $schema)
    {
        $table = $schema->createTable('oro_rfp_assigned_users');
        $table->addColumn('quote_id', 'integer', []);
        $table->addColumn('user_id', 'integer', []);
        $table->setPrimaryKey(['quote_id', 'user_id']);
    }

    /**
     * Add oro_rfp_assigned_acc_users foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroRfpAssignedAccUsersForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_rfp_assigned_acc_users');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account_user'),
            ['account_user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_rfp_request'),
            ['quote_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_rfp_assigned_users foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroRfpAssignedUsersForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_rfp_assigned_users');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_rfp_request'),
            ['quote_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
