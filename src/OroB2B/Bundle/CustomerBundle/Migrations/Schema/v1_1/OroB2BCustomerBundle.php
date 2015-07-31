<?php

namespace OroB2B\Bundle\CustomerBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class OroB2BCustomerBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroB2BAuditFieldTable($schema);
        $this->createOroB2BAuditTable($schema);

        /** Foreign keys generation **/
        $this->addOroB2BAuditFieldForeignKeys($schema);
        $this->addOroB2BAuditForeignKeys($schema);
    }

    /**
     * Create orob2b_audit_field table
     *
     * @param Schema $schema
     */
    protected function createOroB2BAuditFieldTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_audit_field');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('audit_id', 'integer', []);
        $table->addColumn('field', 'string', ['length' => 255]);
        $table->addColumn('data_type', 'string', ['length' => 255]);
        $table->addColumn('old_integer', 'bigint', ['notnull' => false]);
        $table->addColumn('old_float', 'float', ['notnull' => false]);
        $table->addColumn('old_boolean', 'boolean', ['notnull' => false]);
        $table->addColumn('old_text', 'text', ['notnull' => false]);
        $table->addColumn('old_date', 'date', ['notnull' => false, 'comment' => '(DC2Type:date)']);
        $table->addColumn('old_time', 'time', ['notnull' => false, 'comment' => '(DC2Type:time)']);
        $table->addColumn('old_datetime', 'datetime', ['notnull' => false, 'comment' => '(DC2Type:datetime)']);
        $table->addColumn('new_integer', 'bigint', ['notnull' => false]);
        $table->addColumn('new_float', 'float', ['notnull' => false]);
        $table->addColumn('new_boolean', 'boolean', ['notnull' => false]);
        $table->addColumn('new_text', 'text', ['notnull' => false]);
        $table->addColumn('new_date', 'date', ['notnull' => false, 'comment' => '(DC2Type:date)']);
        $table->addColumn('new_time', 'time', ['notnull' => false, 'comment' => '(DC2Type:time)']);
        $table->addColumn('new_datetime', 'datetime', ['notnull' => false, 'comment' => '(DC2Type:datetime)']);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create orob2b_audit table
     *
     * @param Schema $schema
     */
    protected function createOroB2BAuditTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_audit');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_user_id', 'integer', []);
        $table->addColumn('object_name', 'string', ['length' => 255]);
        $table->addColumn('action', 'string', ['length' => 8]);
        $table->addColumn('logged_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('object_id', 'integer', ['notnull' => false]);
        $table->addColumn('object_class', 'string', ['length' => 255]);
        $table->addColumn('version', 'integer', []);
        $table->addIndex(['logged_at'], 'idx_orob2b_audit_logged_at', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add orob2b_audit_field foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BAuditFieldForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_audit_field');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_audit'),
            ['audit_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add orob2b_audit foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BAuditForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_audit');
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
