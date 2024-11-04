<?php

namespace Oro\Bundle\RFPBundle\Migrations\Schema;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareTrait;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareTrait;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\SqlMigrationQuery;
use Oro\Bundle\RFPBundle\Migrations\Data\ORM\LoadRequestCustomerStatuses;
use Oro\Bundle\RFPBundle\Migrations\Data\ORM\LoadRequestInternalStatuses;

/**
 * ORO installer for RFPBundle.
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class OroRFPBundleInstaller implements
    Installation,
    DatabasePlatformAwareInterface,
    ActivityExtensionAwareInterface,
    ExtendExtensionAwareInterface
{
    use DatabasePlatformAwareTrait;
    use ActivityExtensionAwareTrait;
    use ExtendExtensionAwareTrait;

    #[\Override]
    public function getMigrationVersion(): string
    {
        return 'v1_14';
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        /** Tables generation **/
        $this->createOroRfpAssignedAccUsersTable($schema);
        $this->createOroRfpAssignedUsersTable($schema);
        $this->createOroRfpRequestTable($schema, $queries);
        $this->createOroRfpRequestProductTable($schema);
        $this->createOroRfpRequestProductItemTable($schema);
        $this->createOroRfpRequestProductKitItemLineItemTable($schema);
        $this->createOroRfpRequestAddNoteTable($schema);

        /** Foreign keys generation **/
        $this->addOroRfpAssignedAccUsersForeignKeys($schema);
        $this->addOroRfpAssignedUsersForeignKeys($schema);
        $this->addOroRfpRequestForeignKeys($schema);
        $this->addOroRfpRequestProductForeignKeys($schema);
        $this->addOroRfpRequestProductItemForeignKeys($schema);
        $this->addOroRfpRequestProductKitItemLineItemForeignKeys($schema);
        $this->addOroRfpRequestAddNoteForeignKeys($schema);

        $this->addActivityAssociations($schema);
        $this->addOwnerToOroEmailAddress($schema);
    }

    /**
     * Create oro_rfp_assigned_cus_users table
     */
    private function createOroRfpAssignedAccUsersTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_rfp_assigned_cus_users');
        $table->addColumn('request_id', 'integer');
        $table->addColumn('customer_user_id', 'integer');
        $table->setPrimaryKey(['request_id', 'customer_user_id']);
    }

    /**
     * Create oro_rfp_assigned_users table
     */
    private function createOroRfpAssignedUsersTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_rfp_assigned_users');
        $table->addColumn('request_id', 'integer');
        $table->addColumn('user_id', 'integer');
        $table->setPrimaryKey(['request_id', 'user_id']);
    }

    /**
     * Create oro_rfp_request table
     */
    private function createOroRfpRequestTable(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->createTable('oro_rfp_request');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('customer_user_id', 'integer', ['notnull' => false]);
        $table->addColumn('customer_id', 'integer', ['notnull' => false]);
        $table->addColumn('cancellation_reason', 'text', ['notnull' => false]);
        $table->addColumn('first_name', 'string', ['length' => 255]);
        $table->addColumn('last_name', 'string', ['length' => 255]);
        $table->addColumn('email', 'string', ['length' => 255]);
        $table->addColumn('phone', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('company', 'string', ['length' => 255]);
        $table->addColumn('role', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('note', 'text', ['notnull' => false]);
        $table->addColumn('po_number', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('ship_until', 'date', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('deleted_at', 'datetime', ['notnull' => false, 'comment' => '(DC2Type:datetime)']);
        $table->addColumn('website_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['website_id'], 'idx_de1d53c18f45c82');

        $this->addOroRfpRequestEnumField($schema);

        if ($this->platform instanceof PostgreSqlPlatform) {
            $queries->addPostQuery(new SqlMigrationQuery(
                'CREATE INDEX idx_rfp_request_email_ci ON oro_rfp_request (LOWER(email))'
            ));
        }
    }

    private function addOroRfpRequestEnumField(Schema $schema): void
    {
        $this->extendExtension->addEnumField(
            $schema,
            'oro_rfp_request',
            'customer_status',
            'rfp_customer_status',
            false,
            false,
            ['dataaudit' => ['auditable' => true]]
        );

        $enumOptionIds = array_map(
            fn ($key) => ExtendHelper::buildEnumOptionId('rfp_customer_status', $key),
            LoadRequestCustomerStatuses::getDataKeys()
        );
        $schema->getTable('oro_rfp_request')
            ->addExtendColumnOption(
                'customer_status',
                'enum',
                'immutable_codes',
                $enumOptionIds
            );
        $this->extendExtension->addEnumField(
            $schema,
            'oro_rfp_request',
            'internal_status',
            'rfp_internal_status',
            false,
            false,
            ['dataaudit' => ['auditable' => true]]
        );

        $enumOptionIds = array_map(
            fn ($key) => ExtendHelper::buildEnumOptionId('rfp_internal_status', $key),
            LoadRequestInternalStatuses::getDataKeys()
        );
        $schema->getTable('oro_rfp_request')
            ->addExtendColumnOption(
                'internal_status',
                'enum',
                'immutable_codes',
                $enumOptionIds
            );
    }

    /**
     * Create oro_rfp_request_product table
     */
    private function createOroRfpRequestProductTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_rfp_request_product');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => false]);
        $table->addColumn('request_id', 'integer', ['notnull' => false]);
        $table->addColumn('product_sku', 'string', ['length' => 255]);
        $table->addColumn('comment', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_rfp_request_prod_item table
     */
    private function createOroRfpRequestProductItemTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_rfp_request_prod_item');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_unit_id', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('request_product_id', 'integer', ['notnull' => false]);
        $table->addColumn('quantity', 'float', ['notnull' => false]);
        $table->addColumn('product_unit_code', 'string', ['length' => 255]);
        $table->addColumn(
            'value',
            'money',
            [
                'notnull' => false,
                'precision' => 19,
                'scale' => 4,
                'comment' => '(DC2Type:money)',
            ]
        );
        $table->addColumn('currency', 'string', ['notnull' => false, 'length' => 3]);
        $table->addColumn('checksum', 'string', ['length' => 40, 'notnull' => true, 'default' => '']);
        $table->setPrimaryKey(['id']);
    }

    private function createOroRfpRequestAddNoteTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_rfp_request_add_note');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('request_id', 'integer', ['notnull' => false]);
        $table->addColumn('type', 'string', ['length' => 100]);
        $table->addColumn('author', 'string', ['length' => 100]);
        $table->addColumn('user_id', 'integer');
        $table->addColumn('text', 'text');
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add oro_rfp_assigned_cus_users foreign keys.
     */
    private function addOroRfpAssignedAccUsersForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_rfp_assigned_cus_users');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_user'),
            ['customer_user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_rfp_request'),
            ['request_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_rfp_assigned_users foreign keys.
     */
    private function addOroRfpAssignedUsersForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_rfp_assigned_users');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_rfp_request'),
            ['request_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_rfp_request foreign keys.
     */
    private function addOroRfpRequestForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_rfp_request');
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
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_user'),
            ['customer_user_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer'),
            ['customer_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_website'),
            ['website_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }

    /**
     * Add oro_rfp_request_product foreign keys.
     */
    private function addOroRfpRequestProductForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_rfp_request_product');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_rfp_request'),
            ['request_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_rfp_request_prod_item foreign keys.
     */
    private function addOroRfpRequestProductItemForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_rfp_request_prod_item');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product_unit'),
            ['product_unit_id'],
            ['code'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_rfp_request_product'),
            ['request_product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    private function addOroRfpRequestAddNoteForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_rfp_request_add_note');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_rfp_request'),
            ['request_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Enables Email activity for RFP entity
     */
    private function addActivityAssociations(Schema $schema): void
    {
        $this->activityExtension->addActivityAssociation($schema, 'oro_note', 'oro_rfp_request');
        $this->activityExtension->addActivityAssociation($schema, 'oro_email', 'oro_rfp_request');
    }

    private function addOwnerToOroEmailAddress(Schema $schema): void
    {
        $table = $schema->getTable('oro_email_address');
        if ($table->hasColumn('owner_request_id')) {
            return;
        }

        $table->addColumn('owner_request_id', 'integer', ['notnull' => false]);
        $table->addIndex(['owner_request_id']);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_rfp_request'),
            ['owner_request_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
    }

    private function createOroRfpRequestProductKitItemLineItemTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_rfp_request_prod_kit_item_line_item');
        $table->addColumn('id', 'integer', ['notnull' => true, 'autoincrement' => true]);
        $table->addColumn('request_product_id', 'integer', ['notnull' => true]);
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
        $table->setPrimaryKey(['id']);
    }

    private function addOroRfpRequestProductKitItemLineItemForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_rfp_request_prod_kit_item_line_item');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_rfp_request_product'),
            ['request_product_id'],
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
