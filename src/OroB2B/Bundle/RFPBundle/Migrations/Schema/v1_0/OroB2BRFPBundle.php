<?php

namespace OroB2B\Bundle\RFPBundle\Migrations\Schema\v1_0;

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
        /** Tables generation **/
        $this->createOrob2BRfpRequestTable($schema);
        $this->createOrob2BRfpStatusTable($schema);
        $this->createOrob2BRfpStatusTranslationTable($schema);

        /** Foreign keys generation **/
        $this->addOrob2BRfpRequestForeignKeys($schema);
    }

    /**
     * Create orob2b_rfp_request table
     *
     * @param Schema $schema
     */
    protected function createOrob2BRfpRequestTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_rfp_request');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('status_id', 'integer', ['notnull' => false]);
        $table->addColumn('first_name', 'string', ['length' => 255]);
        $table->addColumn('last_name', 'string', ['length' => 255]);
        $table->addColumn('email', 'string', ['length' => 255]);
        $table->addColumn('phone', 'string', ['length' => 255]);
        $table->addColumn('company', 'string', ['length' => 255]);
        $table->addColumn('role', 'string', ['length' => 255]);
        $table->addColumn('body', 'text', []);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['status_id'], 'IDX_512524246BF700BD', []);
    }

    /**
     * Create orob2b_rfp_status table
     *
     * @param Schema $schema
     */
    protected function createOrob2BRfpStatusTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_rfp_status');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('label', 'string', ['length' => 255]);
        $table->addColumn('sort_order', 'integer', []);
        $table->addColumn('deleted', 'boolean', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create orob2b_rfp_status_translation table
     *
     * @param Schema $schema
     */
    protected function createOrob2BRfpStatusTranslationTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_rfp_status_translation');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('foreign_key', 'string', ['length' => 64]);
        $table->addColumn('content', 'string', ['length' => 255]);
        $table->addColumn('locale', 'string', ['length' => 8]);
        $table->addColumn('object_class', 'string', ['length' => 255]);
        $table->addColumn('field', 'string', ['length' => 32]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['locale', 'object_class', 'field', 'foreign_key'], 'orob2b_rfp_status_trans_idx', []);
    }

    /**
     * Add orob2b_rfp_request foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BRfpRequestForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_rfp_request');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_rfp_status'),
            ['status_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
