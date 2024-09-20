<?php

namespace Oro\Bundle\SaleBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareTrait;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareTrait;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareTrait;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\PaymentTermBundle\Migration\Extension\PaymentTermExtensionAwareInterface;
use Oro\Bundle\PaymentTermBundle\Migration\Extension\PaymentTermExtensionAwareTrait;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Migrations\Data\ORM\LoadQuoteCustomerStatuses;
use Oro\Bundle\SaleBundle\Migrations\Data\ORM\LoadQuoteInternalStatuses;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class OroSaleBundleInstaller implements
    Installation,
    AttachmentExtensionAwareInterface,
    ActivityExtensionAwareInterface,
    ExtendExtensionAwareInterface,
    PaymentTermExtensionAwareInterface
{
    use AttachmentExtensionAwareTrait;
    use ActivityExtensionAwareTrait;
    use ExtendExtensionAwareTrait;
    use PaymentTermExtensionAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function getMigrationVersion(): string
    {
        return 'v1_20';
    }

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        /** Tables generation **/
        $this->createOroQuoteAssignedAccUsersTable($schema);
        $this->createOroQuoteAssignedUsersTable($schema);
        $this->createOroSaleQuoteTable($schema);
        $this->createOroQuoteAddressTable($schema);
        $this->createOroSaleQuoteProductTable($schema);
        $this->createOroSaleQuoteProdOfferTable($schema);
        $this->createOroSaleQuoteProductKitItemLineItemTable($schema);
        $this->createOroSaleQuoteProdRequestTable($schema);
        $this->createOroSaleQuoteDemandTable($schema);
        $this->createOroSaleQuoteProductDemandTable($schema);

        /** Foreign keys generation **/
        $this->addOroQuoteAssignedAccUsersForeignKeys($schema);
        $this->addOroQuoteAssignedUsersForeignKeys($schema);
        $this->addOroSaleQuoteForeignKeys($schema);
        $this->addOroSaleQuoteProductForeignKeys($schema);
        $this->addOroSaleQuoteProdOfferForeignKeys($schema);
        $this->addOroSaleQuoteProductKitItemLineItemForeignKeys($schema);
        $this->addOroSaleQuoteProdRequestForeignKeys($schema);
        $this->addOroQuoteAddressForeignKeys($schema);
        $this->addOroSaleQuoteProductDemandForeignKeys($schema);
        $this->addOroSaleQuoteDemandForeignKeys($schema);

        $this->addAttachmentAssociations($schema);
        $this->addActivityAssociations($schema);

        $this->addQuoteCheckoutSource($schema);

        $this->addQuoteCustomerStatusField($schema);
        $this->addQuoteInternalStatusField($schema);

        $this->paymentTermExtension->addPaymentTermAssociation(
            $schema,
            'oro_sale_quote',
            ['datagrid' => ['is_visible' => DatagridScope::IS_VISIBLE_HIDDEN]]
        );

        $this->addAllowUnlistedAndLockMethodFlagsToQuoteTable($schema);
    }

    private function addAllowUnlistedAndLockMethodFlagsToQuoteTable(Schema $schema): void
    {
        $table = $schema->getTable('oro_sale_quote');
        $table->addColumn('shipping_method_locked', 'boolean', ['default' => false]);
        $table->addColumn('allow_unlisted_shipping_method', 'boolean', ['default' => false]);
    }

    /**
     * Create oro_quote_assigned_cus_users table
     */
    private function createOroQuoteAssignedAccUsersTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_quote_assigned_cus_users');
        $table->addColumn('quote_id', 'integer');
        $table->addColumn('customer_user_id', 'integer');
        $table->setPrimaryKey(['quote_id', 'customer_user_id']);
    }

    /**
     * Create oro_quote_assigned_users table
     */
    private function createOroQuoteAssignedUsersTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_quote_assigned_users');
        $table->addColumn('quote_id', 'integer');
        $table->addColumn('user_id', 'integer');
        $table->setPrimaryKey(['quote_id', 'user_id']);
    }

    /**
     * Create oro_sale_quote table
     */
    private function createOroSaleQuoteTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_sale_quote');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('guest_access_id', 'guid', ['notnull' => false]);
        $table->addColumn('customer_user_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('request_id', 'integer', ['notnull' => false]);
        $table->addColumn('customer_id', 'integer', ['notnull' => false]);
        $table->addColumn('shipping_address_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('qid', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('po_number', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('ship_until', 'date', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->addColumn('valid_until', 'datetime', ['notnull' => false]);
        $table->addColumn('expired', 'boolean', ['default' => false]);
        $table->addColumn('prices_changed', 'boolean', ['default' => false]);
        $table->addColumn('website_id', 'integer', ['notnull' => false]);
        $table->addColumn('shipping_method', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('shipping_method_type', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('estimated_shipping_cost_amount', 'money', [
            'notnull' => false,
            'precision' => 19,
            'scale' => 4,
            'comment' => '(DC2Type:money)'
        ]);
        $table->addColumn('override_shipping_cost_amount', 'money', [
            'notnull' => false,
            'precision' => 19,
            'scale' => 4,
            'comment' => '(DC2Type:money)'
        ]);
        $table->addColumn('currency', 'string', ['notnull' => false, 'length' => 3]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['shipping_address_id'], 'UNIQ_4F66B6F64D4CFF2B');
    }

    /**
     * Create oro_quote_address table
     */
    private function createOroQuoteAddressTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_quote_address');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('customer_address_id', 'integer', ['notnull' => false]);
        $table->addColumn('customer_user_address_id', 'integer', ['notnull' => false]);
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
     * Create oro_sale_quote_prod_offer table
     */
    private function createOroSaleQuoteProdOfferTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_sale_quote_prod_offer');
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
        $table->addColumn('checksum', 'string', ['length' => 40, 'notnull' => true, 'default' => '']);
        $table->addColumn('price_type', 'smallint');
        $table->addColumn('allow_increments', 'boolean');
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_sale_quote_prod_request table
     */
    private function createOroSaleQuoteProdRequestTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_sale_quote_prod_request');
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
        $table->addColumn('checksum', 'string', ['length' => 40, 'notnull' => true, 'default' => '']);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_sale_quote_product table
     */
    private function createOroSaleQuoteProductTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_sale_quote_product');
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
        $table->addColumn('comment_customer', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add oro_quote_assigned_cus_users foreign keys.
     */
    private function addOroQuoteAssignedAccUsersForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_quote_assigned_cus_users');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_user'),
            ['customer_user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_sale_quote'),
            ['quote_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_quote_assigned_users foreign keys.
     */
    private function addOroQuoteAssignedUsersForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_quote_assigned_users');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_sale_quote'),
            ['quote_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_sale_quote foreign keys.
     */
    private function addOroSaleQuoteForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_sale_quote');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_user'),
            ['customer_user_id'],
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
            $schema->getTable('oro_rfp_request'),
            ['request_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer'),
            ['customer_id'],
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
            $schema->getTable('oro_website'),
            ['website_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_quote_address'),
            ['shipping_address_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }

    /**
     * Add oro_sale_quote_prod_offer foreign keys.
     */
    private function addOroSaleQuoteProdOfferForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_sale_quote_prod_offer');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product_unit'),
            ['product_unit_id'],
            ['code'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_sale_quote_product'),
            ['quote_product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_sale_quote_prod_request foreign keys.
     */
    private function addOroSaleQuoteProdRequestForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_sale_quote_prod_request');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product_unit'),
            ['product_unit_id'],
            ['code'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_rfp_request_prod_item'),
            ['request_product_item_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_sale_quote_product'),
            ['quote_product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_sale_quote_product foreign keys.
     */
    private function addOroSaleQuoteProductForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_sale_quote_product');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['product_replacement_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_sale_quote'),
            ['quote_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Enable Attachment for Quote entity
     */
    private function addAttachmentAssociations(Schema $schema): void
    {
        $this->attachmentExtension->addAttachmentAssociation(
            $schema,
            'oro_sale_quote',
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
     */
    private function addActivityAssociations(Schema $schema): void
    {
        $this->activityExtension->addActivityAssociation($schema, 'oro_note', 'oro_sale_quote');
        $this->activityExtension->addActivityAssociation($schema, 'oro_email', 'oro_sale_quote', true);
    }

    /**
     * Add oro_quote_address foreign keys.
     */
    private function addOroQuoteAddressForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_quote_address');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_address'),
            ['customer_address_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_user_address'),
            ['customer_user_address_id'],
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

    private function addQuoteCheckoutSource(Schema $schema): void
    {
        if (class_exists('Oro\Bundle\CheckoutBundle\Entity\CheckoutSource')) {
            $this->extendExtension->addManyToOneRelation(
                $schema,
                'oro_checkout_source',
                'quoteDemand',
                'oro_quote_demand',
                'id',
                [
                    'entity' => ['label' => 'oro.sale.quote.entity_label'],
                    'extend' => [
                        'is_extend' => true,
                        'owner' => ExtendScope::OWNER_CUSTOM
                    ],
                    'datagrid' => [
                        'is_visible' => DatagridScope::IS_VISIBLE_FALSE,
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

    private function addQuoteCustomerStatusField(Schema $schema): void
    {
        $this->extendExtension->addEnumField(
            $schema,
            'oro_sale_quote',
            'customer_status',
            Quote::CUSTOMER_STATUS_CODE,
            false,
            false,
            ['dataaudit' => ['auditable' => true]]
        );
        $enumOptionIds = [];
        foreach (LoadQuoteCustomerStatuses::getDataKeys() as $key) {
            $enumOptionIds[] = ExtendHelper::buildEnumOptionId(Quote::CUSTOMER_STATUS_CODE, $key);
        }
        $schema->getTable('oro_sale_quote')
            ->addExtendColumnOption(
                'customer_status',
                'enum',
                'immutable_codes',
                $enumOptionIds
            );
    }

    private function addQuoteInternalStatusField(Schema $schema): void
    {
        $this->extendExtension->addEnumField(
            $schema,
            'oro_sale_quote',
            'internal_status',
            Quote::INTERNAL_STATUS_CODE,
            false,
            false,
            ['dataaudit' => ['auditable' => true]]
        );

        $enumOptionIds = [];
        foreach (LoadQuoteInternalStatuses::getDataKeys() as $key) {
            $enumOptionIds[] = ExtendHelper::buildEnumOptionId(Quote::INTERNAL_STATUS_CODE, $key);
        }
        $schema->getTable('oro_sale_quote')
            ->addExtendColumnOption(
                'internal_status',
                'enum',
                'immutable_codes',
                $enumOptionIds
            );
    }

    /**
     * Create oro_quote_demand table
     */
    private function createOroSaleQuoteDemandTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_quote_demand');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('customer_id', 'integer', ['notnull' => false]);
        $table->addColumn('customer_user_id', 'integer', ['notnull' => false]);
        $table->addColumn('visitor_id', 'integer', ['notnull' => false]);
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
     * Create oro_quote_product_demand table
     */
    private function createOroSaleQuoteProductDemandTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_quote_product_demand');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('quote_demand_id', 'integer', ['notnull' => false]);
        $table->addColumn('quote_product_offer_id', 'integer', ['notnull' => false]);
        $table->addColumn('quantity', 'float');
        $table->addColumn('checksum', 'string', ['length' => 40, 'notnull' => true, 'default' => '']);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add oro_quote_product_demand foreign keys.
     */
    private function addOroSaleQuoteProductDemandForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_quote_product_demand');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_quote_demand'),
            ['quote_demand_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_sale_quote_prod_offer'),
            ['quote_product_offer_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_quote_demand foreign keys.
     */
    private function addOroSaleQuoteDemandForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_quote_demand');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer'),
            ['customer_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_user'),
            ['customer_user_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_sale_quote'),
            ['quote_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_visitor'),
            ['visitor_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    private function createOroSaleQuoteProductKitItemLineItemTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_sale_quote_product_kit_item_line_item');
        $table->addColumn('id', 'integer', ['notnull' => true, 'autoincrement' => true]);
        $table->addColumn('quote_product_id', 'integer', ['notnull' => true]);
        $table->addColumn('product_kit_item_id', 'integer', ['notnull' => false]);
        $table->addColumn('product_kit_item_id_fallback', 'integer', ['notnull' => true]);
        $table->addColumn('product_kit_item_label', 'string', ['notnull' => true, 'length' => 255]);
        $table->addColumn('optional', 'boolean', ['notnull' => true, 'default' => false]);
        $table->addColumn('product_id', 'integer', ['notnull' => false]);
        $table->addColumn('product_id_fallback', 'integer', ['notnull' => true]);
        $table->addColumn('product_sku', 'string', ['notnull' => true, 'length' => 255]);
        $table->addColumn('product_name', 'string', ['notnull' => true, 'length' => 255]);
        $table->addColumn('product_unit_id', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('product_unit_code', 'string', ['notnull' => true, 'length' => 255]);
        $table->addColumn('product_unit_precision', 'integer', ['notnull' => true]);
        $table->addColumn('quantity', 'float', ['notnull' => true]);
        $table->addColumn('minimum_quantity', 'float', ['notnull' => false]);
        $table->addColumn('maximum_quantity', 'float', ['notnull' => false]);
        $table->addColumn('sort_order', 'integer', ['notnull' => true, 'default' => 0]);
        $table->addColumn(
            'value',
            'money',
            ['notnull' => false, 'precision' => 19, 'scale' => 4, 'comment' => '(DC2Type:money)']
        );
        $table->addColumn('currency', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);
    }

    private function addOroSaleQuoteProductKitItemLineItemForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_sale_quote_product_kit_item_line_item');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_sale_quote_product'),
            ['quote_product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product_kit_item'),
            ['product_kit_item_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product_unit'),
            ['product_unit_id'],
            ['code'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
