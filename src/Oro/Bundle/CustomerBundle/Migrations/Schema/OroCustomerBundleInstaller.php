<?php

namespace Oro\Bundle\CustomerBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtension;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareTrait;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ScopeBundle\Migration\Extension\ScopeExtensionAwareInterface;
use Oro\Bundle\ScopeBundle\Migration\Extension\ScopeExtensionAwareTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class OroCustomerBundleInstaller implements
    Installation,
    AttachmentExtensionAwareInterface,
    ActivityExtensionAwareInterface,
    ExtendExtensionAwareInterface,
    ScopeExtensionAwareInterface
{
    use AttachmentExtensionAwareTrait;
    use ScopeExtensionAwareTrait;

    const ORO_CUSTOMER_TABLE_NAME = 'oro_customer';
    const ORO_CUSTOMER_USER_TABLE_NAME = 'oro_customer_user';
    const ORO_CUSTOMER_GROUP_TABLE_NAME = 'oro_customer_group';
    const ORO_CUSTOMER_USER_ORG_TABLE_NAME = 'oro_customer_user_org';
    const ORO_CUSTOMER_ROLE_TO_WEBSITE_TABLE_NAME = 'oro_customer_role_to_website';
    const ORO_WEBSITE_TABLE_NAME = 'oro_website';
    const ORO_ORGANIZATION_TABLE_NAME = 'oro_organization';
    const ORO_CUSTOMER_ADDRESS_TABLE_NAME = 'oro_customer_address';
    const ORO_CUSTOMER_ADDRESS_TO_ADDRESS_TABLE_NAME = 'oro_customer_adr_adr_type';
    const ORO_DICTIONARY_REGION_TABLE_NAME = 'oro_dictionary_region';
    const ORO_DICTIONARY_COUNTRY_TABLE_NAME = 'oro_dictionary_country';
    const ORO_ADDRESS_TYPE_TABLE_NAME = 'oro_address_type';
    const ORO_EMAIL_TABLE_NAME = 'oro_email';
    const ORO_CUSTOMER_USER_ADDRESS_TABLE_NAME = 'oro_customer_user_address';
    const ORO_CUS_USR_ADR_TO_ADR_TYPE_TABLE_NAME = 'oro_cus_usr_adr_to_adr_type';

    const ORO_CATEGORY_TABLE_NAME = 'oro_catalog_category';
    const ORO_PRODUCT_TABLE_NAME = 'oro_product';

    const ORO_USER_TABLE_NAME = 'oro_user';
    const ORO_CUSTOMER_SALES_REPRESENTATIVES_TABLE_NAME = 'oro_customer_sales_reps';
    const ORO_CUSTOMER_USER_SETTINGS = 'oro_customer_user_settings';

    /** @var ExtendExtension */
    protected $extendExtension;

    /** @var ActivityExtension */
    protected $activityExtension;

    /**
     * Sets the ActivityExtension
     *
     * @param ActivityExtension $activityExtension
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
        return 'v1_11';
    }

    /**
     * Sets the ExtendExtension
     *
     * @param ExtendExtension $extendExtension
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroCustomerUserTable($schema);
        $this->createOroCustomerUserOrganizationTable($schema);
        $this->createOroCustomerUserRoleTable($schema);
        $this->createOroCustomerUserAccessCustomerUserRoleTable($schema);
        $this->createOroCustomerUserRoleToWebsiteTable($schema);
        $this->createOroCustomerTable($schema);
        $this->createOroCustomerGroupTable($schema);
        $this->createOroCustomerAddressTable($schema);
        $this->createOroCustomerAdrAdrTypeTable($schema);
        $this->updateOroAuditTable($schema);
        $this->createOroCustomerUserAddressTable($schema);
        $this->createOroCusUsrAdrToAdrTypeTable($schema);
        $this->createOroNavigationHistoryTable($schema);
        $this->createOroNavigationItemTable($schema);
        $this->createOroNavigationItemPinbarTable($schema);
        $this->createOroCustomerUserSdbarStTable($schema);
        $this->createOroCustomerUserSdbarWdgTable($schema);
        $this->createOroAccNavigationPagestateTable($schema);
        $this->createOroCustomerUserSettingsTable($schema);

        $this->createOroCustomerWindowsStateTable($schema);

        $this->createOroCustomerSalesRepresentativesTable($schema);
        $this->createOroCustomerUserSalesRepresentativesTable($schema);

        /** Foreign keys generation **/
        $this->addOroCustomerUserForeignKeys($schema);
        $this->addOroCustomerUserAccessCustomerUserRoleForeignKeys($schema);
        $this->addOroCustomerUserOrganizationForeignKeys($schema);
        $this->addOroCustomerUserRoleForeignKeys($schema);
        $this->addOroCustomerUserRoleToWebsiteForeignKeys($schema);
        $this->addOroCustomerForeignKeys($schema);
        $this->addOroCustomerAddressForeignKeys($schema);
        $this->addOroCustomerAdrAdrTypeForeignKeys($schema);
        $this->addOroCustomerUserAddressForeignKeys($schema);
        $this->addOroCusUsrAdrToAdrTypeForeignKeys($schema);
        $this->addOroNavigationHistoryForeignKeys($schema);
        $this->addOroNavigationItemForeignKeys($schema);
        $this->addOroNavigationItemPinbarForeignKeys($schema);
        $this->addOroCustomerUserSdbarStForeignKeys($schema);
        $this->addOroCustomerUserSdbarWdgForeignKeys($schema);
        $this->addOroAccNavigationPagestateForeignKeys($schema);
        $this->addOroCustomerUserSettingsForeignKeys($schema);

        $this->addOroCustomerWindowsStateForeignKeys($schema);

        $this->addOroCustomerSalesRepresentativesForeignKeys($schema);
        $this->addOroCustomerUserSalesRepresentativesForeignKeys($schema);

        $this->addRelationsToScope($schema);
    }

    /**
     * Create oro_customer_user table
     *
     * @param Schema $schema
     */
    protected function createOroCustomerUserTable(Schema $schema)
    {
        $table = $schema->createTable(static::ORO_CUSTOMER_USER_TABLE_NAME);

        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('owner_id', 'integer', ['notnull' => false]);
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
        $table->addColumn('confirmed', 'boolean', []);
        $table->addColumn('salt', 'string', ['length' => 255]);
        $table->addColumn('password', 'string', ['length' => 255]);
        $table->addColumn('confirmation_token', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('password_requested', 'datetime', ['notnull' => false]);
        $table->addColumn('password_changed', 'datetime', ['notnull' => false]);
        $table->addColumn('last_login', 'datetime', ['notnull' => false]);
        $table->addColumn('login_count', 'integer', ['default' => '0', 'unsigned' => true]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->addColumn('website_id', 'integer', ['notnull' => false]);

        $table->setPrimaryKey(['id']);

        $table->addUniqueIndex(['username']);
        $table->addUniqueIndex(['email']);

        $this->attachmentExtension->addAttachmentAssociation(
            $schema,
            static::ORO_CUSTOMER_USER_TABLE_NAME,
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
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            ]
        );

        $this->activityExtension->addActivityAssociation(
            $schema,
            'oro_note',
            static::ORO_CUSTOMER_USER_TABLE_NAME
        );

        $this->activityExtension->addActivityAssociation(
            $schema,
            static::ORO_EMAIL_TABLE_NAME,
            static::ORO_CUSTOMER_USER_TABLE_NAME
        );
    }

    /**
     * Create oro_customer table
     *
     * @param Schema $schema
     */
    protected function createOroCustomerTable(Schema $schema)
    {
        $table = $schema->createTable(static::ORO_CUSTOMER_TABLE_NAME);

        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('parent_id', 'integer', ['notnull' => false]);
        $table->addColumn('group_id', 'integer', ['notnull' => false]);
        $table->addColumn('owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);

        $table->setPrimaryKey(['id']);

        $table->addIndex(['name'], 'oro_customer_name_idx', []);

        $this->attachmentExtension->addAttachmentAssociation(
            $schema,
            static::ORO_CUSTOMER_TABLE_NAME,
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
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            ]
        );

        $this->activityExtension->addActivityAssociation(
            $schema,
            'oro_note',
            static::ORO_CUSTOMER_TABLE_NAME
        );
        $this->extendExtension->addEnumField(
            $schema,
            static::ORO_CUSTOMER_TABLE_NAME,
            'internal_rating',
            'acc_internal_rating',
            false,
            false,
            ['dataaudit' => ['auditable' => true]]
        );
    }

    /**
     * Create oro_customer_user_access_user_role table
     *
     * @param Schema $schema
     */
    protected function createOroCustomerUserAccessCustomerUserRoleTable(Schema $schema)
    {
        $table = $schema->createTable('oro_cus_user_access_role');

        $table->addColumn('customer_user_id', 'integer', []);
        $table->addColumn('customer_user_role_id', 'integer', []);

        $table->setPrimaryKey(['customer_user_id', 'customer_user_role_id']);
    }

    /**
     * Create oro_customer_group table
     *
     * @param Schema $schema
     */
    protected function createOroCustomerGroupTable(Schema $schema)
    {
        $table = $schema->createTable(static::ORO_CUSTOMER_GROUP_TABLE_NAME);

        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);

        $table->setPrimaryKey(['id']);

        $table->addIndex(['name'], 'oro_customer_group_name_idx', []);

        $this->activityExtension->addActivityAssociation(
            $schema,
            'oro_note',
            static::ORO_CUSTOMER_GROUP_TABLE_NAME
        );
    }

    /**
     * Create oro_audit table
     *
     * @param Schema $schema
     * @todo: BB-2679
     */
    protected function updateOroAuditTable(Schema $schema)
    {
        $auditTable = $schema->getTable('oro_audit');
        $auditTable->addColumn('customer_user_id', 'integer', ['notnull' => false]);

        $auditTable->addForeignKeyConstraint(
            $schema->getTable('oro_customer_user'),
            ['customer_user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Create oro_customer_user_organization table
     *
     * @param Schema $schema
     */
    protected function createOroCustomerUserOrganizationTable(Schema $schema)
    {
        $table = $schema->createTable(static::ORO_CUSTOMER_USER_ORG_TABLE_NAME);

        $table->addColumn('customer_user_id', 'integer', []);
        $table->addColumn('organization_id', 'integer', []);

        $table->setPrimaryKey(['customer_user_id', 'organization_id']);
    }

    /**
     * Create oro_customer_user_role table
     *
     * @param Schema $schema
     */
    protected function createOroCustomerUserRoleTable(Schema $schema)
    {
        $table = $schema->createTable('oro_customer_user_role');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('customer_id', 'integer', ['notnull' => false]);
        $table->addColumn('role', 'string', ['length' => 255]);
        $table->addColumn('label', 'string', ['length' => 255]);
        $table->addColumn('self_managed', 'boolean', ['notnull' => true, 'default' => false]);
        $table->addColumn('public', 'boolean', ['notnull' => true, 'default' => true]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['role']);
        $table->addUniqueIndex(['customer_id', 'label'], 'oro_customer_user_role_customer_id_label_idx');

        $this->activityExtension->addActivityAssociation(
            $schema,
            'oro_note',
            'oro_customer_user_role'
        );
    }

    /**
     * Create oro_customer_role_to_website table
     *
     * @param Schema $schema
     */
    protected function createOroCustomerUserRoleToWebsiteTable(Schema $schema)
    {
        $table = $schema->createTable(static::ORO_CUSTOMER_ROLE_TO_WEBSITE_TABLE_NAME);
        $table->addColumn('customer_user_role_id', 'integer', []);
        $table->addColumn('website_id', 'integer', []);
        $table->setPrimaryKey(['customer_user_role_id', 'website_id']);
        $table->addUniqueIndex(['website_id']);
    }

    /**
     * Create oro_customer_address table
     *
     * @param Schema $schema
     */
    protected function createOroCustomerAddressTable(Schema $schema)
    {
        $table = $schema->createTable(static::ORO_CUSTOMER_ADDRESS_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('system_org_id', 'integer', ['notnull' => false]);
        $table->addColumn('frontend_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('region_code', 'string', ['notnull' => false, 'length' => 16]);
        $table->addColumn('country_code', 'string', ['notnull' => false, 'length' => 2]);
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
        $table->addColumn('phone', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('created', 'datetime', []);
        $table->addColumn('updated', 'datetime', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_customer_adr_adr_type table
     *
     * @param Schema $schema
     */
    protected function createOroCustomerAdrAdrTypeTable(Schema $schema)
    {
        $table = $schema->createTable(static::ORO_CUSTOMER_ADDRESS_TO_ADDRESS_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('type_name', 'string', ['notnull' => false, 'length' => 16]);
        $table->addColumn('customer_address_id', 'integer', ['notnull' => false]);
        $table->addColumn('is_default', 'boolean', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['customer_address_id', 'type_name'], 'oro_customer_adr_id_type_name_idx');
    }

    /**
     * Create oro_navigation_history table
     *
     * @param Schema $schema
     */
    protected function createOroNavigationHistoryTable(Schema $schema)
    {
        $table = $schema->createTable('oro_cus_navigation_history');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('customer_user_id', 'integer', []);
        $table->addColumn('url', 'string', ['length' => 1023]);
        $table->addColumn('title', 'text', []);
        $table->addColumn('visited_at', 'datetime', []);
        $table->addColumn('visit_count', 'integer', []);
        $table->addColumn('route', 'string', ['length' => 128]);
        $table->addColumn('route_parameters', 'array', ['comment' => '(DC2Type:array)']);
        $table->addColumn('entity_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['route'], 'oro_cus_nav_history_route_idx');
        $table->addIndex(['entity_id'], 'oro_cus_nav_history_entity_id_idx');
    }

    /**
     * Create oro_navigation_item table
     *
     * @param Schema $schema
     */
    protected function createOroNavigationItemTable(Schema $schema)
    {
        $table = $schema->createTable('oro_cus_navigation_item');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('customer_user_id', 'integer', []);
        $table->addColumn('type', 'string', ['length' => 20]);
        $table->addColumn('url', 'string', ['length' => 1023]);
        $table->addColumn('title', 'text', []);
        $table->addColumn('position', 'smallint', []);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['customer_user_id', 'position'], 'oro_sorted_items_idx', []);
    }

    /**
     * Create oro_cus_nav_item_pinbar table
     *
     * @param Schema $schema
     */
    protected function createOroNavigationItemPinbarTable(Schema $schema)
    {
        $table = $schema->createTable('oro_cus_nav_item_pinbar');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('item_id', 'integer', []);
        $table->addColumn('maximized', 'datetime', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['item_id'], 'UNIQ_F6DC70B5126F525E');
    }

    /**
     * Create oro_customer_user_sdbar_st table
     *
     * @param Schema $schema
     */
    protected function createOroCustomerUserSdbarStTable(Schema $schema)
    {
        $table = $schema->createTable('oro_customer_user_sdbar_st');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('customer_user_id', 'integer', []);
        $table->addColumn('position', 'string', ['length' => 13]);
        $table->addColumn('state', 'string', ['length' => 17]);
        $table->addUniqueIndex(['customer_user_id', 'position'], 'oro_cus_sdbar_st_unq_idx');
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_customer_user_sdbar_wdg table
     *
     * @param Schema $schema
     */
    protected function createOroCustomerUserSdbarWdgTable(Schema $schema)
    {
        $table = $schema->createTable('oro_customer_user_sdbar_wdg');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('customer_user_id', 'integer', []);
        $table->addColumn('placement', 'string', ['length' => 50]);
        $table->addColumn('position', 'smallint', []);
        $table->addColumn('widget_name', 'string', ['length' => 50]);
        $table->addColumn('settings', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->addColumn('state', 'string', ['length' => 22]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['position'], 'oro_cus_sdar_wdgs_pos_idx', []);
        $table->addIndex(['customer_user_id', 'placement'], 'oro_cus_sdbr_wdgs_usr_place_idx', []);
    }

    /**
     * Create oro_cus_pagestate table
     *
     * @param Schema $schema
     */
    protected function createOroAccNavigationPagestateTable(Schema $schema)
    {
        $table = $schema->createTable('oro_cus_pagestate');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('customer_user_id', 'integer', []);
        $table->addColumn('page_id', 'string', ['length' => 4000]);
        $table->addColumn('page_hash', 'string', ['length' => 32]);
        $table->addColumn('data', 'text', []);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['page_hash'], 'UNIQ_993DC655567C7E62');
    }

    /**
     * Create oro_customer_sales_representatives table
     *
     * @param Schema $schema
     */
    protected function createOroCustomerSalesRepresentativesTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_CUSTOMER_SALES_REPRESENTATIVES_TABLE_NAME);
        $table->addColumn('customer_id', 'integer');
        $table->addColumn('user_id', 'integer');
        $table->setPrimaryKey(['customer_id', 'user_id']);
    }

    /**
     * Create oro_customer_user_sales_representatives table
     *
     * @param Schema $schema
     */
    protected function createOroCustomerUserSalesRepresentativesTable(Schema $schema)
    {
        $table = $schema->createTable('oro_customer_user_sales_reps');
        $table->addColumn('customer_user_id', 'integer');
        $table->addColumn('user_id', 'integer');
        $table->setPrimaryKey(['customer_user_id', 'user_id']);
    }

    /**
     * @param Schema $schema
     */
    protected function createOroCustomerUserSettingsTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_CUSTOMER_USER_SETTINGS);

        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('customer_user_id', 'integer');
        $table->addColumn('website_id', 'integer');
        $table->addColumn('currency', 'string', ['length' => 3, 'notnull' => false]);
        $table->addColumn('localization_id', 'integer', ['notnull' => false]);

        $table->setPrimaryKey(['id']);
    }

    /**
     * Add oro_customer_user foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCustomerUserForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(static::ORO_CUSTOMER_USER_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(static::ORO_ORGANIZATION_TABLE_NAME),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(static::ORO_CUSTOMER_TABLE_NAME),
            ['customer_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_website'),
            ['website_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_customer_user_access_user_role foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCustomerUserAccessCustomerUserRoleForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_cus_user_access_role');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_user_role'),
            ['customer_user_role_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(static::ORO_CUSTOMER_USER_TABLE_NAME),
            ['customer_user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_customer foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCustomerForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(static::ORO_CUSTOMER_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(static::ORO_CUSTOMER_GROUP_TABLE_NAME),
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
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(static::ORO_ORGANIZATION_TABLE_NAME),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_customer_user_organization foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCustomerUserOrganizationForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(static::ORO_CUSTOMER_USER_ORG_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(static::ORO_CUSTOMER_USER_TABLE_NAME),
            ['customer_user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(static::ORO_ORGANIZATION_TABLE_NAME),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_customer_user_role foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCustomerUserRoleForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_customer_user_role');
        $table->addForeignKeyConstraint(
            $schema->getTable(static::ORO_ORGANIZATION_TABLE_NAME),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(static::ORO_CUSTOMER_TABLE_NAME),
            ['customer_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_customer_role_to_website foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCustomerUserRoleToWebsiteForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(static::ORO_CUSTOMER_ROLE_TO_WEBSITE_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(static::ORO_WEBSITE_TABLE_NAME),
            ['website_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_user_role'),
            ['customer_user_role_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_customer_address foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCustomerAddressForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(static::ORO_CUSTOMER_ADDRESS_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(static::ORO_CUSTOMER_TABLE_NAME),
            ['frontend_owner_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(static::ORO_DICTIONARY_REGION_TABLE_NAME),
            ['region_code'],
            ['combined_code'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(static::ORO_DICTIONARY_COUNTRY_TABLE_NAME),
            ['country_code'],
            ['iso2_code'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['owner_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['system_org_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }

    /**
     * Add oro_customer_adr_adr_type foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCustomerAdrAdrTypeForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(static::ORO_CUSTOMER_ADDRESS_TO_ADDRESS_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(static::ORO_ADDRESS_TYPE_TABLE_NAME),
            ['type_name'],
            ['name'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(static::ORO_CUSTOMER_ADDRESS_TABLE_NAME),
            ['customer_address_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Create oro_customer_user_address table
     *
     * @param Schema $schema
     */
    protected function createOroCustomerUserAddressTable(Schema $schema)
    {
        $table = $schema->createTable(static::ORO_CUSTOMER_USER_ADDRESS_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('system_org_id', 'integer', ['notnull' => false]);
        $table->addColumn('region_code', 'string', ['notnull' => false, 'length' => 16]);
        $table->addColumn('frontend_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('country_code', 'string', ['notnull' => false, 'length' => 2]);
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
        $table->addColumn('phone', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('created', 'datetime', []);
        $table->addColumn('updated', 'datetime', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add oro_customer_user_address foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCustomerUserAddressForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(static::ORO_CUSTOMER_USER_ADDRESS_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(static::ORO_DICTIONARY_REGION_TABLE_NAME),
            ['region_code'],
            ['combined_code'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(static::ORO_CUSTOMER_USER_TABLE_NAME),
            ['frontend_owner_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(static::ORO_DICTIONARY_COUNTRY_TABLE_NAME),
            ['country_code'],
            ['iso2_code'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['owner_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['system_org_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }

    /**
     * Create oro_customer_adr_to_adr_type table
     *
     * @param Schema $schema
     */
    protected function createOroCusUsrAdrToAdrTypeTable(Schema $schema)
    {
        $table = $schema->createTable(static::ORO_CUS_USR_ADR_TO_ADR_TYPE_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('type_name', 'string', ['notnull' => false, 'length' => 16]);
        $table->addColumn('customer_user_address_id', 'integer', ['notnull' => false]);
        $table->addColumn('is_default', 'boolean', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['customer_user_address_id', 'type_name'], 'oro_customer_user_adr_id_type_name_idx');
    }

    /**
     * Add oro_customer_adr_to_adr_type foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCusUsrAdrToAdrTypeForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(static::ORO_CUS_USR_ADR_TO_ADR_TYPE_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(static::ORO_ADDRESS_TYPE_TABLE_NAME),
            ['type_name'],
            ['name'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(static::ORO_CUSTOMER_USER_ADDRESS_TABLE_NAME),
            ['customer_user_address_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_navigation_history foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroNavigationHistoryForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_cus_navigation_history');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_user'),
            ['customer_user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_navigation_item foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroNavigationItemForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_cus_navigation_item');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_user'),
            ['customer_user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_cus_nav_item_pinbar foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroNavigationItemPinbarForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_cus_nav_item_pinbar');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_cus_navigation_item'),
            ['item_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_customer_user_sdbar_st foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCustomerUserSdbarStForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_customer_user_sdbar_st');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_user'),
            ['customer_user_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_customer_user_sdbar_wdg foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCustomerUserSdbarWdgForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_customer_user_sdbar_wdg');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_user'),
            ['customer_user_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_cus_navigation_pagestate foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroAccNavigationPagestateForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_cus_pagestate');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_user'),
            ['customer_user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Create oro_cus_windows_state table
     *
     * @param Schema $schema
     */
    protected function createOroCustomerWindowsStateTable(Schema $schema)
    {
        $table = $schema->createTable('oro_cus_windows_state');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('customer_user_id', 'integer', []);
        $table->addColumn('data', Type::JSON_ARRAY, ['comment' => '(DC2Type:json_array)']);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addIndex(['customer_user_id'], 'oro_cus_windows_state_acu_idx', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add oro_cus_windows_state foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCustomerWindowsStateForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_cus_windows_state');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_user'),
            ['customer_user_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_customer_sales_representatives foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCustomerSalesRepresentativesForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_CUSTOMER_SALES_REPRESENTATIVES_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_USER_TABLE_NAME),
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_CUSTOMER_TABLE_NAME),
            ['customer_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_customer_user_sales_representatives foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCustomerUserSalesRepresentativesForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_customer_user_sales_reps');
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_USER_TABLE_NAME),
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_user'),
            ['customer_user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * @param Schema $schema
     */
    protected function addOroCustomerUserSettingsForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_CUSTOMER_USER_SETTINGS);

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_user'),
            ['customer_user_id'],
            ['id'],
            ['onDelete' => 'CASCADE'],
            'fk_customer_user_id'
        );

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_website'),
            ['website_id'],
            ['id'],
            ['onDelete' => 'CASCADE'],
            'fk_website_id'
        );

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_localization'),
            ['localization_id'],
            ['id'],
            ['onDelete' => 'SET NULL'],
            'fk_localization_id'
        );

        $table->addUniqueIndex(['customer_user_id', 'website_id'], 'unique_cus_user_website');
    }

    /**
     * @param Schema $schema
     * @todo: BB-2679
     */
    private function addRelationsToScope(Schema $schema)
    {
        $this->scopeExtension->addScopeAssociation(
            $schema,
            'customerGroup',
            OroCustomerBundleInstaller::ORO_CUSTOMER_GROUP_TABLE_NAME,
            'name'
        );

        $this->scopeExtension->addScopeAssociation(
            $schema,
            'customer',
            OroCustomerBundleInstaller::ORO_CUSTOMER_TABLE_NAME,
            'name'
        );
    }
}
