<?php

namespace OroB2B\Bundle\SaleBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtension;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtension;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtension;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtensionAwareInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class OroB2BSaleBundleInstaller implements
    Installation,
    NoteExtensionAwareInterface,
    AttachmentExtensionAwareInterface,
    ActivityExtensionAwareInterface,
    ExtendExtensionAwareInterface
{
    /**
     * @var ExtendExtension
     */
    protected $extendExtension;

    /**
     * @var AttachmentExtension
     */
    protected $attachmentExtension;

    /**
     * @var NoteExtension
     */
    protected $noteExtension;

    /**
     * @var ActivityExtension
     */
    protected $activityExtension;

    /**
     * {@inheritdoc}
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

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
        return 'v1_8';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroQuoteAssignedAccUsersTable($schema);
        $this->createOroQuoteAssignedUsersTable($schema);
        $this->createOroB2BSaleQuoteTable($schema);
        $this->createOroB2BQuoteAddressTable($schema);
        $this->createOroB2BSaleQuoteProductTable($schema);
        $this->createOroB2BSaleQuoteProdOfferTable($schema);
        $this->createOroB2BSaleQuoteProdRequestTable($schema);

        $this->createOroB2BSaleQuoteDemandTable($schema);
        $this->createOroB2BSaleQuoteProductDemandTable($schema);

        /** Foreign keys generation **/
        $this->addOroQuoteAssignedAccUsersForeignKeys($schema);
        $this->addOroQuoteAssignedUsersForeignKeys($schema);
        $this->addOroB2BSaleQuoteForeignKeys($schema);
        $this->addOroB2BSaleQuoteProductForeignKeys($schema);
        $this->addOroB2BSaleQuoteProdOfferForeignKeys($schema);
        $this->addOroB2BSaleQuoteProdRequestForeignKeys($schema);
        $this->addOroB2BQuoteAddressForeignKeys($schema);
        $this->addOroB2BSaleQuoteProductDemandForeignKeys($schema);
        $this->addOroB2BSaleQuoteDemandForeignKeys($schema);

        $this->addNoteAssociations($schema, $this->noteExtension);
        $this->addAttachmentAssociations($schema, $this->attachmentExtension);
        $this->addActivityAssociations($schema, $this->activityExtension);

        $this->addQuoteCheckoutSource($schema);
    }

    /**
     * Create oro_quote_assigned_acc_users table
     *
     * @param Schema $schema
     */
    protected function createOroQuoteAssignedAccUsersTable(Schema $schema)
    {
        $table = $schema->createTable('oro_quote_assigned_acc_users');
        $table->addColumn('quote_id', 'integer', []);
        $table->addColumn('account_user_id', 'integer', []);
        $table->setPrimaryKey(['quote_id', 'account_user_id']);
    }

    /**
     * Create oro_quote_assigned_users table
     *
     * @param Schema $schema
     */
    protected function createOroQuoteAssignedUsersTable(Schema $schema)
    {
        $table = $schema->createTable('oro_quote_assigned_users');
        $table->addColumn('quote_id', 'integer', []);
        $table->addColumn('user_id', 'integer', []);
        $table->setPrimaryKey(['quote_id', 'user_id']);
    }

    /**
     * Create orob2b_sale_quote table
     *
     * @param Schema $schema
     */
    protected function createOroB2BSaleQuoteTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_sale_quote');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('account_user_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('request_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_id', 'integer', ['notnull' => false]);
        $table->addColumn('shipping_address_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('qid', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('po_number', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('ship_until', 'date', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->addColumn('valid_until', 'datetime', ['notnull' => false]);
        $table->addColumn('locked', 'boolean');
        $table->addColumn('expired', 'boolean', ['default' => false]);
        $table->addColumn('website_id', 'integer', ['notnull' => false]);
        $table->addColumn('shipping_estimate_amount', 'money', [
            'notnull' => false,
            'precision' => 19,
            'scale' => 4,
            'comment' => '(DC2Type:money)'
        ]);
        $table->addColumn('shipping_estimate_currency', 'string', ['notnull' => false, 'length' => 3]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['shipping_address_id'], 'UNIQ_4F66B6F64D4CFF2B');
    }

    /**
     * Create orob2b_quote_address table
     *
     * @param Schema $schema
     */
    protected function createOroB2BQuoteAddressTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_quote_address');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('account_address_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_user_address_id', 'integer', ['notnull' => false]);
        $table->addColumn('region_code', 'string', ['notnull' => false, 'length' => 16]);
        $table->addColumn('country_code', 'string', ['notnull' => false, 'length' => 2]);
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
        $table->addColumn('phone', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('created', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create orob2b_sale_quote_prod_offer table
     *
     * @param Schema $schema
     */
    protected function createOroB2BSaleQuoteProdOfferTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_sale_quote_prod_offer');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_unit_id', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('quote_product_id', 'integer', ['notnull' => false]);
        $table->addColumn('product_unit_code', 'string', ['length' => 255]);
        $table->addColumn('quantity', 'float', ['notnull' => false]);
        $table->addColumn('value', 'money', [
            'notnull' => false,
            'precision' => 19,
            'scale' => 4,
            'comment' => '(DC2Type:money)'
        ]);
        $table->addColumn('currency', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('price_type', 'smallint', []);
        $table->addColumn('allow_increments', 'boolean', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create orob2b_sale_quote_prod_request table
     *
     * @param Schema $schema
     */
    protected function createOroB2BSaleQuoteProdRequestTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_sale_quote_prod_request');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_unit_id', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('request_product_item_id', 'integer', ['notnull' => false]);
        $table->addColumn('quote_product_id', 'integer', ['notnull' => false]);
        $table->addColumn('product_unit_code', 'string', ['length' => 255]);
        $table->addColumn('quantity', 'float', ['notnull' => false]);
        $table->addColumn('value', 'money', [
            'notnull' => false,
            'precision' => 19,
            'scale' => 4,
            'comment' => '(DC2Type:money)'
        ]);
        $table->addColumn('currency', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create orob2b_sale_quote_product table
     *
     * @param Schema $schema
     */
    protected function createOroB2BSaleQuoteProductTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_sale_quote_product');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => false]);
        $table->addColumn('product_replacement_id', 'integer', ['notnull' => false]);
        $table->addColumn('quote_id', 'integer', ['notnull' => false]);
        $table->addColumn('product_sku', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('product_replacement_sku', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('type', 'smallint', ['notnull' => false]);
        $table->addColumn('free_form_product', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('free_form_product_replacement', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('comment', 'text', ['notnull' => false]);
        $table->addColumn('comment_account', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add oro_quote_assigned_acc_users foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroQuoteAssignedAccUsersForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_quote_assigned_acc_users');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account_user'),
            ['account_user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_sale_quote'),
            ['quote_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_quote_assigned_users foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroQuoteAssignedUsersForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_quote_assigned_users');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_sale_quote'),
            ['quote_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_sale_quote foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BSaleQuoteForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_sale_quote');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account_user'),
            ['account_user_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_rfp_request'),
            ['request_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account'),
            ['account_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_website'),
            ['website_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_quote_address'),
            ['shipping_address_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }

    /**
     * Add orob2b_sale_quote_prod_offer foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BSaleQuoteProdOfferForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_sale_quote_prod_offer');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product_unit'),
            ['product_unit_id'],
            ['code'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_sale_quote_product'),
            ['quote_product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_sale_quote_prod_request foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BSaleQuoteProdRequestForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_sale_quote_prod_request');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product_unit'),
            ['product_unit_id'],
            ['code'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_rfp_request_prod_item'),
            ['request_product_item_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_sale_quote_product'),
            ['quote_product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_sale_quote_product foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BSaleQuoteProductForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_sale_quote_product');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product'),
            ['product_replacement_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_sale_quote'),
            ['quote_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Enable notes for Quote entity
     *
     * @param Schema $schema
     * @param NoteExtension $noteExtension
     */
    protected function addNoteAssociations(Schema $schema, NoteExtension $noteExtension)
    {
        $noteExtension->addNoteAssociation($schema, 'orob2b_sale_quote');
    }

    /**
     * Enable Attachment for Quote entity
     *
     * @param Schema $schema
     * @param AttachmentExtension $attachmentExtension
     */
    protected function addAttachmentAssociations(Schema $schema, AttachmentExtension $attachmentExtension)
    {
        $attachmentExtension->addAttachmentAssociation(
            $schema,
            'orob2b_sale_quote',
            [
                'image/*',
                'application/pdf',
                'application/zip',
                'application/x-gzip',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation'
            ],
            2
        );
    }

    /**
     * Enable Events for Quote entity
     *
     * @param Schema $schema
     * @param ActivityExtension $activityExtension
     */
    protected function addActivityAssociations(Schema $schema, ActivityExtension $activityExtension)
    {
        $activityExtension->addActivityAssociation($schema, 'oro_calendar_event', 'orob2b_sale_quote');
        $activityExtension->addActivityAssociation($schema, 'oro_email', 'orob2b_sale_quote', true);
    }

    /**
     * Add orob2b_quote_address foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BQuoteAddressForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_quote_address');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account_address'),
            ['account_address_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account_user_address'),
            ['account_user_address_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
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
    }

    /**
     * @param Schema $schema
     */
    protected function addQuoteCheckoutSource(Schema $schema)
    {
        if (class_exists('OroB2B\Bundle\CheckoutBundle\Entity\CheckoutSource')) {
            $this->extendExtension->addManyToOneRelation(
                $schema,
                'orob2b_checkout_source',
                'quoteDemand',
                'orob2b_quote_demand',
                'id',
                [
                    ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_READONLY,
                    'entity' => ['label' => 'orob2b.sale.quote.entity_label'],
                    'extend' => [
                        'is_extend' => true,
                        'owner' => ExtendScope::OWNER_CUSTOM
                    ],
                    'datagrid' => [
                        'is_visible' => false
                    ],
                    'form' => [
                        'is_enabled' => false
                    ],
                    'view' => ['is_displayable' => false],
                    'merge' => ['display' => false],
                    'dataaudit' => ['auditable' => false]
                ]
            );
        }
    }

    /**
     * Create orob2b_quote_demand table
     *
     * @param Schema $schema
     */
    protected function createOroB2BSaleQuoteDemandTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_quote_demand');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('quote_id', 'integer', ['notnull' => false]);
        $table->addColumn(
            'subtotal',
            'money',
            ['notnull' => false, 'precision' => 19, 'scale' => 4, 'comment' => '(DC2Type:money)']
        );
        $table->addColumn(
            'total',
            'money',
            ['notnull' => false, 'precision' => 19, 'scale' => 4, 'comment' => '(DC2Type:money)']
        );
        $table->addColumn('total_currency', 'string', ['notnull' => false, 'length' => 3]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create orob2b_quote_product_demand table
     *
     * @param Schema $schema
     */
    protected function createOroB2BSaleQuoteProductDemandTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_quote_product_demand');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('quote_demand_id', 'integer', ['notnull' => false]);
        $table->addColumn('quote_product_offer_id', 'integer', ['notnull' => false]);
        $table->addColumn('quantity', 'float', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add orob2b_quote_product_demand foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BSaleQuoteProductDemandForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_quote_product_demand');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_quote_demand'),
            ['quote_demand_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_sale_quote_prod_offer'),
            ['quote_product_offer_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_quote_demand foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BSaleQuoteDemandForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_quote_demand');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_sale_quote'),
            ['quote_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
