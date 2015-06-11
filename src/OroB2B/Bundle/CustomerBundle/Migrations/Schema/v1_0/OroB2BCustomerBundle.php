<?php

namespace OroB2B\Bundle\CustomerBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BCustomerBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroB2BAccountUserTable($schema);
        $this->createOroB2BAccountUserOrganizationTable($schema);
        $this->createOroB2BCustomerTable($schema);
        $this->createOroB2BCustomerGroupTable($schema);

        /** Foreign keys generation **/
        $this->addOroB2BAccountUserForeignKeys($schema);
        $this->addOroB2BAccountUserOrganizationForeignKeys($schema);
        $this->addOroB2BCustomerForeignKeys($schema);
    }

    /**
     * Create orob2b_account_user table
     *
     * @param Schema $schema
     */
    protected function createOroB2BAccountUserTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_account_user');

        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('customer_id', 'integer', ['notnull' => false]);
        $table->addColumn('username', 'string', ['length' => 255]);
        $table->addColumn('email', 'string', ['length' => 255]);
        $table->addColumn('name_prefix', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('first_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('middle_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('last_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('name_suffix', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('birthday', 'date', ['notnull' => false]);
        $table->addColumn('enabled', 'boolean', []);
        $table->addColumn('salt', 'string', ['length' => 255]);
        $table->addColumn('password', 'string', ['length' => 255]);
        $table->addColumn('confirmation_token', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('password_requested', 'datetime', ['notnull' => false]);
        $table->addColumn('password_changed', 'datetime', ['notnull' => false]);
        $table->addColumn('last_login', 'datetime', ['notnull' => false]);
        $table->addColumn('login_count', 'integer', ['default' => '0', 'unsigned' => true]);
        $table->addColumn('createdAt', 'datetime', []);
        $table->addColumn('updatedAt', 'datetime', []);

        $table->setPrimaryKey(['id']);

        $table->addUniqueIndex(['username'], 'UNIQ_689CD865F85E0677');
        $table->addUniqueIndex(['email'], 'UNIQ_689CD865E7927C74');
    }

    /**
     * Create orob2b_customer table
     *
     * @param Schema $schema
     */
    protected function createOroB2BCustomerTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_customer');

        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('parent_id', 'integer', ['notnull' => false]);
        $table->addColumn('group_id', 'integer', ['notnull' => false]);

        $table->setPrimaryKey(['id']);

        $table->addIndex(['name'], 'orob2b_customer_name_idx', []);
    }

    /**
     * Create orob2b_customer_group table
     *
     * @param Schema $schema
     */
    protected function createOroB2BCustomerGroupTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_customer_group');

        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);

        $table->setPrimaryKey(['id']);

        $table->addIndex(['name'], 'orob2b_customer_group_name_idx', []);
    }

    /**
     * Create orob2b_account_user_organization table
     *
     * @param Schema $schema
     */
    protected function createOroB2BAccountUserOrganizationTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_account_user_organiz');

        $table->addColumn('account_user_id', 'integer', []);
        $table->addColumn('organization_id', 'integer', []);

        $table->setPrimaryKey(['account_user_id', 'organization_id']);
    }

    /**
     * Add orob2b_account_user foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BAccountUserForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_account_user');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_customer'),
            ['customer_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_customer foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BCustomerForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_customer');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_customer_group'),
            ['group_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $table,
            ['parent_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_account_user_organization foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BAccountUserOrganizationForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_account_user_organiz');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account_user'),
            ['account_user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
