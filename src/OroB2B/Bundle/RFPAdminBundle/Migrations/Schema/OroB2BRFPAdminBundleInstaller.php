<?php

namespace OroB2B\Bundle\RFPAdminBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtension;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtension;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtensionAwareInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class OroB2BRFPAdminBundleInstaller implements
    Installation,
    NoteExtensionAwareInterface,
    ActivityExtensionAwareInterface
{
    /**
     * @var ActivityExtension
     */
    protected $activityExtension;

    /**
     * @var NoteExtension
     */
    protected $noteExtension;

    /**
     * {@inheritdoc}
     */
    public function setNoteExtension(NoteExtension $noteExtension)
    {
        $this->noteExtension = $noteExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function setActivityExtension(ActivityExtension $activityExtension)
    {
        $this->activityExtension = $activityExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_1';
    }

    /**
     * Enable notes for RFP entity
     *
     * @param Schema        $schema
     * @param NoteExtension $noteExtension
     */
    protected function addNoteAssociations(Schema $schema, NoteExtension $noteExtension)
    {
        $noteExtension->addNoteAssociation($schema, 'orob2b_rfp_request');
    }

    /**
     * Enables Email activity for RFP entity
     *
     * @param Schema            $schema
     * @param ActivityExtension $activityExtension
     */
    protected function addActivityAssociations(Schema $schema, ActivityExtension $activityExtension)
    {
        $activityExtension->addActivityAssociation($schema, 'oro_email', 'orob2b_rfp_request');
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOrob2BRfpRequestTable($schema);
        $this->createOrob2BRfpRequestProductTable($schema);
        $this->createOrob2BRfpRequestProductItemTable($schema);
        $this->createOrob2BRfpStatusTable($schema);
        $this->createOrob2BRfpStatusTranslationTable($schema);

        /** Foreign keys generation **/
        $this->addOrob2BRfpRequestForeignKeys($schema);
        $this->addOrob2BRfpRequestProductForeignKeys($schema);
        $this->addOrob2BRfpRequestProductItemForeignKeys($schema);
        $this->addOrob2BRfpStatusTranslationForeignKeys($schema);

        $this->addNoteAssociations($schema, $this->noteExtension);
        $this->addActivityAssociations($schema, $this->activityExtension);
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
        $table->addColumn('serialized_data', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create orob2b_rfp_request_product table
     *
     * @param Schema $schema
     */
    protected function createOrob2BRfpRequestProductTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_rfp_request_product');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => false]);
        $table->addColumn('request_id', 'integer', ['notnull' => false]);
        $table->addColumn('product_sku', 'string', ['length' => 255]);
        $table->addColumn('comment', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create orob2b_rfp_request_prod_item table
     *
     * @param Schema $schema
     */
    protected function createOrob2BRfpRequestProductItemTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_rfp_request_prod_item');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_unit_id', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('request_product_id', 'integer', ['notnull' => false]);
        $table->addColumn('quantity', 'float', []);
        $table->addColumn('product_unit_code', 'string', ['length' => 255]);
        $table->addColumn('value', 'money', ['precision' => 19, 'scale' => 4, 'comment' => '(DC2Type:money)']);
        $table->addColumn('currency', 'string', ['length' => 3]);
        $table->setPrimaryKey(['id']);
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
        $table->addColumn('label', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('sort_order', 'integer', ['notnull' => false]);
        $table->addColumn('deleted', 'boolean', ['default' => '0']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['name'], 'orob2b_rfp_status_name_idx', []);
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
        $table->addColumn('object_id', 'integer', ['notnull' => false]);
        $table->addColumn('locale', 'string', ['length' => 8]);
        $table->addColumn('field', 'string', ['length' => 32]);
        $table->addColumn('content', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['locale', 'object_id', 'field'], 'orob2b_rfp_status_trans_idx', []);
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

    /**
     * Add orob2b_rfp_request_product foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BRfpRequestProductForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_rfp_request_product');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_rfp_request'),
            ['request_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_rfp_request_prod_item foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BRfpRequestProductItemForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_rfp_request_prod_item');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product_unit'),
            ['product_unit_id'],
            ['code'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_rfp_request_product'),
            ['request_product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_rfp_status_translation foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BRfpStatusTranslationForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_rfp_status_translation');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_rfp_status'),
            ['object_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
