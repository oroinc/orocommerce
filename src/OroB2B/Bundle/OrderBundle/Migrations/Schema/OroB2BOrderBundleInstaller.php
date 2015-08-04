<?php

namespace OroB2B\Bundle\OrderBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtension;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtensionAwareInterface;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtension;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtension;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;

class OroB2BOrderBundleInstaller implements
    Installation,
    NoteExtensionAwareInterface,
    AttachmentExtensionAwareInterface,
    ActivityExtensionAwareInterface
{
    /** @var  NoteExtension */
    protected $noteExtension;

    /** @var  AttachmentExtension */
    protected $attachmentExtension;

    /** @var  ActivityExtension */
    protected $activityExtension;

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
    public function setAttachmentExtension(AttachmentExtension $attachmentExtension)
    {
        $this->attachmentExtension = $attachmentExtension;
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
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroB2BOrderTable($schema);
        $this->createOroB2BOrderAddressTable($schema);
        $this->createOroB2BOrderAddressTypeTable($schema);

        /** Foreign keys generation **/
        $this->addOroB2BOrderForeignKeys($schema);
        $this->addOroB2BOrderAddressForeignKeys($schema);
        $this->addOroB2BOrderAddressTypeForeignKeys($schema);
    }

    /**
     * Create orob2b_order table
     *
     * @param Schema $schema
     */
    protected function createOroB2BOrderTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_order');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('identifier', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('poNumber', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('customerNotes', 'text', ['notnull' => false]);
        $table->addColumn('shipUntil', 'date', ['notnull' => false]);
        $table->addColumn('currency', 'string', ['notnull' => false, 'length' => 3]);
        $table->addColumn(
            'subtotal',
            'money',
            ['notnull' => false, 'precision' => 19, 'scale' => 4, 'comment' => '(DC2Type:money)']
        );
        $table->addColumn('payment_term_id', 'integer', ['notnull' => false]);
        $table->addUniqueIndex(['identifier'], 'uniq_orob2b_order_identifier');
        $table->setPrimaryKey(['id']);
        $table->addIndex(['user_owner_id'], 'idx_c036ff909eb185f9', []);
        $table->addIndex(['organization_id'], 'idx_c036ff9032c8a3de', []);
        $table->addIndex(['created_at'], 'created_at_index', []);
        $table->addIndex(['payment_term_id'], 'IDX_C036FF9017653B16', []);

        $this->noteExtension->addNoteAssociation($schema, $table->getName());
        $this->attachmentExtension->addAttachmentAssociation($schema, $table->getName());
        $this->activityExtension->addActivityAssociation($schema, 'oro_calendar_event', $table->getName());
    }

    /**
     * Create orob2b_order_address table
     *
     * @param Schema $schema
     */
    protected function createOroB2BOrderAddressTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_order_address');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('region_code', 'string', ['notnull' => false, 'length' => 16]);
        $table->addColumn('country_code', 'string', ['notnull' => false, 'length' => 2]);
        $table->addColumn('owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('is_primary', 'boolean', ['notnull' => false]);
        $table->addColumn('label', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('street', 'string', ['notnull' => false, 'length' => 500]);
        $table->addColumn('street2', 'string', ['notnull' => false, 'length' => 500]);
        $table->addColumn('city', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('postal_code', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('organization', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('region_text', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('name_prefix', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('first_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('middle_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('last_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('name_suffix', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('created', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addIndex(['region_code'], 'idx_ff867c56aeb327af', []);
        $table->addIndex(['country_code'], 'idx_ff867c56f026bb7c', []);
        $table->addIndex(['owner_id'], 'idx_ff867c567e3c61f9', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create orob2b_order_address_type table
     *
     * @param Schema $schema
     */
    protected function createOroB2BOrderAddressTypeTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_order_address_type');
        $table->addColumn('order_address_id', 'integer', []);
        $table->addColumn('type_name', 'string', ['length' => 16]);
        $table->setPrimaryKey(['order_address_id', 'type_name']);
        $table->addIndex(['type_name'], 'idx_31dd983d892cbb0e', []);
        $table->addIndex(['order_address_id'], 'idx_31dd983d466d5220', []);
    }

    /**
     * Add orob2b_order foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BOrderForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_order');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_payment_term'),
            ['payment_term_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }

    /**
     * Add orob2b_order_address foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BOrderAddressForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_order_address');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_dictionary_region'),
            ['region_code'],
            ['combined_code'],
            ['onUpdate' => null, 'onDelete' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_dictionary_country'),
            ['country_code'],
            ['iso2_code'],
            ['onUpdate' => null, 'onDelete' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_order'),
            ['owner_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add orob2b_order_address_type foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BOrderAddressTypeForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_order_address_type');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_address_type'),
            ['type_name'],
            ['name'],
            ['onUpdate' => null, 'onDelete' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_order_address'),
            ['order_address_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}
