<?php

namespace Oro\Bundle\CustomerBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtension;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtension;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtension;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtensionAwareInterface;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\ScopeBundle\Migrations\Schema\OroScopeBundleInstaller;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class OroAccountBundleInstaller implements
    Installation,
    AttachmentExtensionAwareInterface,
    NoteExtensionAwareInterface,
    ActivityExtensionAwareInterface,
    ExtendExtensionAwareInterface
{
    const ORO_ACCOUNT_TABLE_NAME = 'oro_account';
    const ORO_ACCOUNT_USER_TABLE_NAME = 'oro_account_user';
    const ORO_ACC_USER_ACCESS_ROLE_TABLE_NAME = 'oro_acc_user_access_role';
    const ORO_ACCOUNT_GROUP_TABLE_NAME = 'oro_account_group';
    const ORO_ACCOUNT_USER_ORG_TABLE_NAME = 'oro_account_user_org';
    const ORO_ACCOUNT_USER_ROLE_TABLE_NAME = 'oro_account_user_role';
    const ORO_ACCOUNT_ROLE_TO_WEBSITE_TABLE_NAME = 'oro_account_role_to_website';
    const ORO_WEBSITE_TABLE_NAME = 'oro_website';
    const ORO_ORGANIZATION_TABLE_NAME = 'oro_organization';
    const ORO_ACCOUNT_ADDRESS_TABLE_NAME = 'oro_account_address';
    const ORO_ACCOUNT_ADDRESS_TO_ADDRESS_TABLE_NAME = 'oro_account_adr_adr_type';
    const ORO_DICTIONARY_REGION_TABLE_NAME = 'oro_dictionary_region';
    const ORO_DICTIONARY_COUNTRY_TABLE_NAME = 'oro_dictionary_country';
    const ORO_ADDRESS_TYPE_TABLE_NAME = 'oro_address_type';
    const ORO_EMAIL = 'oro_email';
    const ORO_ACCOUNT_USER_ADDRESS_TABLE_NAME = 'oro_account_user_address';
    const ORO_ACC_USR_ADR_TO_ADR_TYPE_TABLE_NAME = 'oro_acc_usr_adr_to_adr_type';

    const ORO_CATEGORY_VISIBILITY_TABLE_NAME = 'oro_category_visibility';
    const ORO_ACCOUNT_CATEGORY_VISIBILITY_TABLE_NAME = 'oro_acc_category_visibility';
    const ORO_ACCOUNT_GROUP_CATEGORY_VISIBILITY_TABLE_NAME = 'oro_acc_grp_ctgr_visibility';
    const ORO_CATEGORY_TABLE_NAME = 'oro_catalog_category';

    const ORO_PRODUCT_VISIBILITY_TABLE_NAME = 'oro_product_visibility';
    const ORO_ACCOUNT_PRODUCT_VISIBILITY_TABLE_NAME = 'oro_acc_product_visibility';
    const ORO_ACCOUNT_GROUP_PRODUCT_VISIBILITY_TABLE_NAME = 'oro_acc_grp_prod_visibility';
    const ORO_PRODUCT_TABLE_NAME = 'oro_product';

    const ORO_PRODUCT_VISIBILITY_RESOLVED = 'oro_prod_vsb_resolv';
    const ORO_ACCOUNT_GROUP_PRODUCT_VISIBILITY_RESOLVED = 'oro_acc_grp_prod_vsb_resolv';
    const ORO_ACCOUNT_PRODUCT_VISIBILITY_RESOLVED = 'oro_acc_prod_vsb_resolv';

    const ORO_CATEGORY_VISIBILITY_RESOLVED = 'oro_ctgr_vsb_resolv';
    const ORO_ACCOUNT_GROUP_CATEGORY_VISIBILITY_RESOLVED = 'oro_acc_grp_ctgr_vsb_resolv';
    const ORO_ACCOUNT_CATEGORY_VISIBILITY_RESOLVED = 'oro_acc_ctgr_vsb_resolv';

    const ORO_USER_TABLE_NAME = 'oro_user';
    const ORO_ACCOUNT_SALES_REPRESENTATIVES_TABLE_NAME = 'oro_account_sales_reps';
    const ORO_ACCOUNT_USER_SALES_REPRESENTATIVES_TABLE_NAME = 'oro_account_user_sales_reps';
    const ORO_ACCOUNT_USER_SETTINGS = 'oro_account_user_settings';

    /** @var ExtendExtension */
    protected $extendExtension;

    /** @var NoteExtension */
    protected $noteExtension;

    /** @var AttachmentExtension */
    protected $attachmentExtension;

    /** @var ActivityExtension */
    protected $activityExtension;

    /**
     * Sets the AttachmentExtension
     *
     * @param AttachmentExtension $attachmentExtension
     */
    public function setAttachmentExtension(AttachmentExtension $attachmentExtension)
    {
        $this->attachmentExtension = $attachmentExtension;
    }

    /**
     * Sets the NoteExtension
     *
     * @param NoteExtension $noteExtension
     */
    public function setNoteExtension(NoteExtension $noteExtension)
    {
        $this->noteExtension = $noteExtension;
    }

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
        return 'v1_8';
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
        $this->createOroAccountUserTable($schema);
        $this->createOroAccountUserOrganizationTable($schema);
        $this->createOroAccountUserRoleTable($schema);
        $this->createOroAccountUserAccessAccountUserRoleTable($schema);
        $this->createOroAccountUserRoleToWebsiteTable($schema);
        $this->createOroAccountTable($schema);
        $this->createOroAccountGroupTable($schema);
        $this->createOroAccountAddressTable($schema);
        $this->createOroAccountAdrAdrTypeTable($schema);
        $this->updateOroAuditTable($schema);
        $this->createOroAccountUserAddressTable($schema);
        $this->createOroAccUsrAdrToAdrTypeTable($schema);
        $this->createOroNavigationHistoryTable($schema);
        $this->createOroNavigationItemTable($schema);
        $this->createOroNavigationItemPinbarTable($schema);
        $this->createOroAccountUserSdbarStTable($schema);
        $this->createOroAccountUserSdbarWdgTable($schema);
        $this->createOroAccNavigationPagestateTable($schema);
        $this->createOroAccountUserSettingsTable($schema);

        $this->createOroCategoryVisibilityTable($schema);
        $this->createOroAccountCategoryVisibilityTable($schema);
        $this->createOroAccountGroupCategoryVisibilityTable($schema);

        $this->createOroProductVisibilityTable($schema);
        $this->createOroAccountProductVisibilityTable($schema);
        $this->createOroAccountGroupProductVisibilityTable($schema);

        $this->createOroAccountWindowsStateTable($schema);
        $this->createOroProductVisibilityResolvedTable($schema);
        $this->createOroAccountGroupProductVisibilityResolvedTable($schema);
        $this->createOroAccountProductVisibilityResolvedTable($schema);

        $this->createOroAccountSalesRepresentativesTable($schema);
        $this->createOroAccountUserSalesRepresentativesTable($schema);

        $this->createOroCategoryVisibilityResolvedTable($schema);
        $this->createOroAccountGroupCategoryVisibilityResolvedTable($schema);
        $this->createOroAccountCategoryVisibilityResolvedTable($schema);

        /** Foreign keys generation **/
        $this->addOroAccountUserForeignKeys($schema);
        $this->addOroAccountUserAccessAccountUserRoleForeignKeys($schema);
        $this->addOroAccountUserOrganizationForeignKeys($schema);
        $this->addOroAccountUserRoleForeignKeys($schema);
        $this->addOroAccountUserRoleToWebsiteForeignKeys($schema);
        $this->addOroAccountForeignKeys($schema);
        $this->addOroAccountAddressForeignKeys($schema);
        $this->addOroAccountAdrAdrTypeForeignKeys($schema);
        $this->addOroAccountUserAddressForeignKeys($schema);
        $this->addOroAccUsrAdrToAdrTypeForeignKeys($schema);
        $this->addOroNavigationHistoryForeignKeys($schema);
        $this->addOroNavigationItemForeignKeys($schema);
        $this->addOroNavigationItemPinbarForeignKeys($schema);
        $this->addOroAccountUserSdbarStForeignKeys($schema);
        $this->addOroAccountUserSdbarWdgForeignKeys($schema);
        $this->addOroAccNavigationPagestateForeignKeys($schema);
        $this->addOroAccountUserSettingsForeignKeys($schema);

        $this->addOroProductVisibilityForeignKeys($schema);
        $this->addOroAccountProductVisibilityForeignKeys($schema);
        $this->addOroAccountGroupProductVisibilityForeignKeys($schema);

        $this->addOroAccountWindowsStateForeignKeys($schema);
        $this->addOroProductVisibilityResolvedForeignKeys($schema);
        $this->addOroAccountGroupProductVisibilityResolvedForeignKeys($schema);
        $this->addOroAccountProductVisibilityResolvedForeignKeys($schema);

        $this->addOroAccountSalesRepresentativesForeignKeys($schema);
        $this->addOroAccountUserSalesRepresentativesForeignKeys($schema);

        $this->addOroCategoryVisibilityForeignKeys($schema);
        $this->addOroAccountCategoryVisibilityForeignKeys($schema);
        $this->addOroAccountGroupCategoryVisibilityForeignKeys($schema);

        $this->addOroCategoryVisibilityResolvedForeignKeys($schema);
        $this->addOroAccountGroupCategoryVisibilityResolvedForeignKeys($schema);
        $this->addOroAccountCategoryVisibilityResolvedForeignKeys($schema);

        $this->addRelationsToScope($schema);
    }

    /**
     * Create oro_account_user table
     *
     * @param Schema $schema
     */
    protected function createOroAccountUserTable(Schema $schema)
    {
        $table = $schema->createTable(static::ORO_ACCOUNT_USER_TABLE_NAME);

        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_id', 'integer', ['notnull' => false]);
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
            static::ORO_ACCOUNT_USER_TABLE_NAME,
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

        $this->noteExtension->addNoteAssociation($schema, static::ORO_ACCOUNT_USER_TABLE_NAME);

        $this->activityExtension->addActivityAssociation(
            $schema,
            static::ORO_EMAIL,
            static::ORO_ACCOUNT_USER_TABLE_NAME
        );
    }

    /**
     * Create oro_account table
     *
     * @param Schema $schema
     */
    protected function createOroAccountTable(Schema $schema)
    {
        $table = $schema->createTable(static::ORO_ACCOUNT_TABLE_NAME);

        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('parent_id', 'integer', ['notnull' => false]);
        $table->addColumn('group_id', 'integer', ['notnull' => false]);
        $table->addColumn('owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);

        $table->setPrimaryKey(['id']);

        $table->addIndex(['name'], 'oro_account_name_idx', []);

        $this->attachmentExtension->addAttachmentAssociation(
            $schema,
            static::ORO_ACCOUNT_TABLE_NAME,
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

        $this->noteExtension->addNoteAssociation($schema, static::ORO_ACCOUNT_TABLE_NAME);
        $this->extendExtension->addEnumField(
            $schema,
            static::ORO_ACCOUNT_TABLE_NAME,
            'internal_rating',
            Account::INTERNAL_RATING_CODE,
            false,
            false,
            ['dataaudit' => ['auditable' => true]]
        );
    }

    /**
     * Create oro_account_user_access_user_role table
     *
     * @param Schema $schema
     */
    protected function createOroAccountUserAccessAccountUserRoleTable(Schema $schema)
    {
        $table = $schema->createTable(static::ORO_ACC_USER_ACCESS_ROLE_TABLE_NAME);

        $table->addColumn('account_user_id', 'integer', []);
        $table->addColumn('account_user_role_id', 'integer', []);

        $table->setPrimaryKey(['account_user_id', 'account_user_role_id']);
    }

    /**
     * Create oro_account_group table
     *
     * @param Schema $schema
     */
    protected function createOroAccountGroupTable(Schema $schema)
    {
        $table = $schema->createTable(static::ORO_ACCOUNT_GROUP_TABLE_NAME);

        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);

        $table->setPrimaryKey(['id']);

        $table->addIndex(['name'], 'oro_account_group_name_idx', []);

        $this->noteExtension->addNoteAssociation($schema, static::ORO_ACCOUNT_GROUP_TABLE_NAME);
    }

    /**
     * Create oro_audit table
     *
     * @param Schema $schema
     */
    protected function updateOroAuditTable(Schema $schema)
    {
        $auditTable = $schema->getTable('oro_audit');
        $auditTable->addColumn('account_user_id', 'integer', ['notnull' => false]);

        $auditTable->addForeignKeyConstraint(
            $schema->getTable('oro_account_user'),
            ['account_user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Create oro_account_user_organization table
     *
     * @param Schema $schema
     */
    protected function createOroAccountUserOrganizationTable(Schema $schema)
    {
        $table = $schema->createTable(static::ORO_ACCOUNT_USER_ORG_TABLE_NAME);

        $table->addColumn('account_user_id', 'integer', []);
        $table->addColumn('organization_id', 'integer', []);

        $table->setPrimaryKey(['account_user_id', 'organization_id']);
    }

    /**
     * Create oro_account_user_role table
     *
     * @param Schema $schema
     */
    protected function createOroAccountUserRoleTable(Schema $schema)
    {
        $table = $schema->createTable(static::ORO_ACCOUNT_USER_ROLE_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_id', 'integer', ['notnull' => false]);
        $table->addColumn('role', 'string', ['length' => 255]);
        $table->addColumn('label', 'string', ['length' => 255]);
        $table->addColumn('self_managed', 'boolean', ['notnull' => true, 'default' => false]);
        $table->addColumn('public', 'boolean', ['notnull' => true, 'default' => true]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['role']);
        $table->addUniqueIndex(['account_id', 'label'], 'oro_account_user_role_account_id_label_idx');

        $this->noteExtension->addNoteAssociation($schema, static::ORO_ACCOUNT_USER_ROLE_TABLE_NAME);
    }

    /**
     * Create oro_account_role_to_website table
     *
     * @param Schema $schema
     */
    protected function createOroAccountUserRoleToWebsiteTable(Schema $schema)
    {
        $table = $schema->createTable(static::ORO_ACCOUNT_ROLE_TO_WEBSITE_TABLE_NAME);
        $table->addColumn('account_user_role_id', 'integer', []);
        $table->addColumn('website_id', 'integer', []);
        $table->setPrimaryKey(['account_user_role_id', 'website_id']);
        $table->addUniqueIndex(['website_id']);
    }

    /**
     * Create oro_account_address table
     *
     * @param Schema $schema
     */
    protected function createOroAccountAddressTable(Schema $schema)
    {
        $table = $schema->createTable(static::ORO_ACCOUNT_ADDRESS_TABLE_NAME);
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
     * Create oro_account_adr_adr_type table
     *
     * @param Schema $schema
     */
    protected function createOroAccountAdrAdrTypeTable(Schema $schema)
    {
        $table = $schema->createTable(static::ORO_ACCOUNT_ADDRESS_TO_ADDRESS_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('type_name', 'string', ['notnull' => false, 'length' => 16]);
        $table->addColumn('account_address_id', 'integer', ['notnull' => false]);
        $table->addColumn('is_default', 'boolean', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['account_address_id', 'type_name'], 'oro_account_adr_id_type_name_idx');
    }

    /**
     * Create oro_navigation_history table
     *
     * @param Schema $schema
     */
    protected function createOroNavigationHistoryTable(Schema $schema)
    {
        $table = $schema->createTable('oro_acc_navigation_history');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_user_id', 'integer', []);
        $table->addColumn('url', 'string', ['length' => 1023]);
        $table->addColumn('title', 'text', []);
        $table->addColumn('visited_at', 'datetime', []);
        $table->addColumn('visit_count', 'integer', []);
        $table->addColumn('route', 'string', ['length' => 128]);
        $table->addColumn('route_parameters', 'array', ['comment' => '(DC2Type:array)']);
        $table->addColumn('entity_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['route'], 'oro_acc_nav_history_route_idx');
        $table->addIndex(['entity_id'], 'oro_acc_nav_history_entity_id_idx');
    }

    /**
     * Create oro_navigation_item table
     *
     * @param Schema $schema
     */
    protected function createOroNavigationItemTable(Schema $schema)
    {
        $table = $schema->createTable('oro_acc_navigation_item');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_user_id', 'integer', []);
        $table->addColumn('type', 'string', ['length' => 20]);
        $table->addColumn('url', 'string', ['length' => 1023]);
        $table->addColumn('title', 'text', []);
        $table->addColumn('position', 'smallint', []);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['account_user_id', 'position'], 'oro_sorted_items_idx', []);
    }

    /**
     * Create oro_acc_nav_item_pinbar table
     *
     * @param Schema $schema
     */
    protected function createOroNavigationItemPinbarTable(Schema $schema)
    {
        $table = $schema->createTable('oro_acc_nav_item_pinbar');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('item_id', 'integer', []);
        $table->addColumn('maximized', 'datetime', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['item_id'], 'UNIQ_F6DC70B5126F525E');
    }

    /**
     * Create oro_account_user_sdbar_st table
     *
     * @param Schema $schema
     */
    protected function createOroAccountUserSdbarStTable(Schema $schema)
    {
        $table = $schema->createTable('oro_account_user_sdbar_st');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('account_user_id', 'integer', []);
        $table->addColumn('position', 'string', ['length' => 13]);
        $table->addColumn('state', 'string', ['length' => 17]);
        $table->addUniqueIndex(['account_user_id', 'position'], 'oro_acc_sdbar_st_unq_idx');
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_account_user_sdbar_wdg table
     *
     * @param Schema $schema
     */
    protected function createOroAccountUserSdbarWdgTable(Schema $schema)
    {
        $table = $schema->createTable('oro_account_user_sdbar_wdg');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_user_id', 'integer', []);
        $table->addColumn('placement', 'string', ['length' => 50]);
        $table->addColumn('position', 'smallint', []);
        $table->addColumn('widget_name', 'string', ['length' => 50]);
        $table->addColumn('settings', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->addColumn('state', 'string', ['length' => 22]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['position'], 'oro_acc_sdar_wdgs_pos_idx', []);
        $table->addIndex(['account_user_id', 'placement'], 'oro_acc_sdbr_wdgs_usr_place_idx', []);
    }

    /**
     * Create oro_acc_pagestate table
     *
     * @param Schema $schema
     */
    protected function createOroAccNavigationPagestateTable(Schema $schema)
    {
        $table = $schema->createTable('oro_acc_pagestate');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('account_user_id', 'integer', []);
        $table->addColumn('page_id', 'string', ['length' => 4000]);
        $table->addColumn('page_hash', 'string', ['length' => 32]);
        $table->addColumn('data', 'text', []);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['page_hash'], 'UNIQ_993DC655567C7E62');
    }

    /**
     * Create oro_prod_vsb_resolv table
     *
     * @param Schema $schema
     */
    protected function createOroProductVisibilityResolvedTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_PRODUCT_VISIBILITY_RESOLVED);
        $table->addColumn('website_id', 'integer', []);
        $table->addColumn('product_id', 'integer', []);
        $table->addColumn('source_product_visibility', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'smallint', ['notnull' => false]);
        $table->addColumn('source', 'smallint', ['notnull' => false]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['website_id', 'product_id']);
    }

    /**
     * Create oro_acc_grp_prod_vsb_resolv table
     *
     * @param Schema $schema
     */
    protected function createOroAccountGroupProductVisibilityResolvedTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_ACCOUNT_GROUP_PRODUCT_VISIBILITY_RESOLVED);
        $table->addColumn('account_group_id', 'integer', []);
        $table->addColumn('website_id', 'integer', []);
        $table->addColumn('product_id', 'integer', []);
        $table->addColumn('source_product_visibility', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'smallint', ['notnull' => false]);
        $table->addColumn('source', 'smallint', ['notnull' => false]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['account_group_id', 'website_id', 'product_id']);
    }

    /**
     * Create oro_acc_prod_vsb_resolv table
     *
     * @param Schema $schema
     */
    protected function createOroAccountProductVisibilityResolvedTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_ACCOUNT_PRODUCT_VISIBILITY_RESOLVED);
        $table->addColumn('account_id', 'integer', []);
        $table->addColumn('website_id', 'integer', []);
        $table->addColumn('product_id', 'integer', []);
        $table->addColumn('source_product_visibility', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'smallint', ['notnull' => false]);
        $table->addColumn('source', 'smallint', ['notnull' => false]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['account_id', 'website_id', 'product_id']);
    }

    /**
     * Create oro_account_sales_representatives table
     *
     * @param Schema $schema
     */
    protected function createOroAccountSalesRepresentativesTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_ACCOUNT_SALES_REPRESENTATIVES_TABLE_NAME);
        $table->addColumn('account_id', 'integer');
        $table->addColumn('user_id', 'integer');
        $table->setPrimaryKey(['account_id', 'user_id']);
    }

    /**
     * Create oro_account_user_sales_representatives table
     *
     * @param Schema $schema
     */
    protected function createOroAccountUserSalesRepresentativesTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_ACCOUNT_USER_SALES_REPRESENTATIVES_TABLE_NAME);
        $table->addColumn('account_user_id', 'integer');
        $table->addColumn('user_id', 'integer');
        $table->setPrimaryKey(['account_user_id', 'user_id']);
    }

    /**
     * Create oro_ctgr_vsb_resolv table
     *
     * @param Schema $schema
     */
    protected function createOroCategoryVisibilityResolvedTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_CATEGORY_VISIBILITY_RESOLVED);
        $table->addColumn('source_category_visibility', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'smallint', ['notnull' => false]);
        $table->addColumn('source', 'smallint', ['notnull' => false]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['category_id']);
    }

    /**
     * Create oro_acc_grp_ctgr_vsb_resolv table
     *
     * @param Schema $schema
     */
    protected function createOroAccountGroupCategoryVisibilityResolvedTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_ACCOUNT_GROUP_CATEGORY_VISIBILITY_RESOLVED);
        $table->addColumn('account_group_id', 'integer', []);
        $table->addColumn('source_category_visibility', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'smallint', ['notnull' => false]);
        $table->addColumn('source', 'smallint', ['notnull' => false]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['account_group_id', 'category_id']);
    }

    /**
     * Create oro_acc_ctgr_vsb_resolv table
     *
     * @param Schema $schema
     */
    protected function createOroAccountCategoryVisibilityResolvedTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_ACCOUNT_CATEGORY_VISIBILITY_RESOLVED);
        $table->addColumn('account_id', 'integer', []);
        $table->addColumn('source_category_visibility', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'smallint', ['notnull' => false]);
        $table->addColumn('source', 'smallint', ['notnull' => false]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['account_id', 'category_id']);
    }

    /**
     * @param Schema $schema
     */
    protected function createOroAccountUserSettingsTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_ACCOUNT_USER_SETTINGS);

        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('account_user_id', 'integer');
        $table->addColumn('website_id', 'integer');
        $table->addColumn('currency', 'string', ['length' => 3, 'notnull' => false]);
        $table->addColumn('localization_id', 'integer', ['notnull' => false]);

        $table->setPrimaryKey(['id']);
    }

    /**
     * Add oro_account_user foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroAccountUserForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(static::ORO_ACCOUNT_USER_TABLE_NAME);
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
            $schema->getTable(static::ORO_ACCOUNT_TABLE_NAME),
            ['account_id'],
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
     * Add oro_account_user_access_user_role foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroAccountUserAccessAccountUserRoleForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(static::ORO_ACC_USER_ACCESS_ROLE_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(static::ORO_ACCOUNT_USER_ROLE_TABLE_NAME),
            ['account_user_role_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(static::ORO_ACCOUNT_USER_TABLE_NAME),
            ['account_user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_account foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroAccountForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(static::ORO_ACCOUNT_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(static::ORO_ACCOUNT_GROUP_TABLE_NAME),
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
     * Add oro_account_user_organization foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroAccountUserOrganizationForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(static::ORO_ACCOUNT_USER_ORG_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(static::ORO_ACCOUNT_USER_TABLE_NAME),
            ['account_user_id'],
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
     * Add oro_account_user_role foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroAccountUserRoleForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(static::ORO_ACCOUNT_USER_ROLE_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(static::ORO_ORGANIZATION_TABLE_NAME),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(static::ORO_ACCOUNT_TABLE_NAME),
            ['account_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_account_role_to_website foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroAccountUserRoleToWebsiteForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(static::ORO_ACCOUNT_ROLE_TO_WEBSITE_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(static::ORO_WEBSITE_TABLE_NAME),
            ['website_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(static::ORO_ACCOUNT_USER_ROLE_TABLE_NAME),
            ['account_user_role_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_account_address foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroAccountAddressForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(static::ORO_ACCOUNT_ADDRESS_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(static::ORO_ACCOUNT_TABLE_NAME),
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
     * Add oro_account_adr_adr_type foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroAccountAdrAdrTypeForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(static::ORO_ACCOUNT_ADDRESS_TO_ADDRESS_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(static::ORO_ADDRESS_TYPE_TABLE_NAME),
            ['type_name'],
            ['name'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(static::ORO_ACCOUNT_ADDRESS_TABLE_NAME),
            ['account_address_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Create oro_account_user_address table
     *
     * @param Schema $schema
     */
    protected function createOroAccountUserAddressTable(Schema $schema)
    {
        $table = $schema->createTable(static::ORO_ACCOUNT_USER_ADDRESS_TABLE_NAME);
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
     * Add oro_account_user_address foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroAccountUserAddressForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(static::ORO_ACCOUNT_USER_ADDRESS_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(static::ORO_DICTIONARY_REGION_TABLE_NAME),
            ['region_code'],
            ['combined_code'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(static::ORO_ACCOUNT_USER_TABLE_NAME),
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
     * Create oro_account_adr_to_adr_type table
     *
     * @param Schema $schema
     */
    protected function createOroAccUsrAdrToAdrTypeTable(Schema $schema)
    {
        $table = $schema->createTable(static::ORO_ACC_USR_ADR_TO_ADR_TYPE_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('type_name', 'string', ['notnull' => false, 'length' => 16]);
        $table->addColumn('account_user_address_id', 'integer', ['notnull' => false]);
        $table->addColumn('is_default', 'boolean', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['account_user_address_id', 'type_name'], 'oro_account_user_adr_id_type_name_idx');
    }

    /**
     * Add oro_account_adr_to_adr_type foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroAccUsrAdrToAdrTypeForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(static::ORO_ACC_USR_ADR_TO_ADR_TYPE_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(static::ORO_ADDRESS_TYPE_TABLE_NAME),
            ['type_name'],
            ['name'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(static::ORO_ACCOUNT_USER_ADDRESS_TABLE_NAME),
            ['account_user_address_id'],
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
        $table = $schema->getTable('oro_acc_navigation_history');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_account_user'),
            ['account_user_id'],
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
        $table = $schema->getTable('oro_acc_navigation_item');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_account_user'),
            ['account_user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_acc_nav_item_pinbar foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroNavigationItemPinbarForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_acc_nav_item_pinbar');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_acc_navigation_item'),
            ['item_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_account_user_sdbar_st foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroAccountUserSdbarStForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_account_user_sdbar_st');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_account_user'),
            ['account_user_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_account_user_sdbar_wdg foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroAccountUserSdbarWdgForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_account_user_sdbar_wdg');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_account_user'),
            ['account_user_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_acc_navigation_pagestate foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroAccNavigationPagestateForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_acc_pagestate');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_account_user'),
            ['account_user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Create oro_category_visibility table
     *
     * @param Schema $schema
     */
    protected function createOroCategoryVisibilityTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_CATEGORY_VISIBILITY_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'string', ['length' => 255, 'notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['category_id'], 'oro_ctgr_vis_uidx');
    }

    /**
     * Create oro_acc_category_visibility table
     *
     * @param Schema $schema
     */
    protected function createOroAccountCategoryVisibilityTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_ACCOUNT_CATEGORY_VISIBILITY_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_id', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'string', ['length' => 255, 'notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['category_id', 'account_id'], 'oro_acc_ctgr_vis_uidx');
    }

    /**
     * Create oro_acc_grp_ctgr_visibility table
     *
     * @param Schema $schema
     */
    protected function createOroAccountGroupCategoryVisibilityTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_ACCOUNT_GROUP_CATEGORY_VISIBILITY_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_group_id', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'string', ['length' => 255, 'notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['category_id', 'account_group_id'], 'oro_acc_grp_ctgr_vis_uidx');
    }

    /**
     * Create oro_product_visibility table
     *
     * @param Schema $schema
     */
    protected function createOroProductVisibilityTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_PRODUCT_VISIBILITY_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => false]);
        $table->addColumn('website_id', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'string', ['length' => 255, 'notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['website_id', 'product_id'], 'oro_prod_vis_uidx');
    }

    /**
     * Create oro_acc_product_visibility table
     *
     * @param Schema $schema
     */
    protected function createOroAccountProductVisibilityTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_ACCOUNT_PRODUCT_VISIBILITY_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => false]);
        $table->addColumn('website_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_id', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'string', ['length' => 255, 'notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['website_id', 'product_id', 'account_id'], 'oro_acc_prod_vis_uidx');
    }

    /**
     * Create oro_acc_grp_prod_visibility table
     *
     * @param Schema $schema
     */
    protected function createOroAccountGroupProductVisibilityTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_ACCOUNT_GROUP_PRODUCT_VISIBILITY_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => false]);
        $table->addColumn('website_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_group_id', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'string', ['length' => 255, 'notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['website_id', 'product_id', 'account_group_id'], 'oro_acc_grp_prod_vis_uidx');
    }

    /**
     * Create oro_acc_windows_state table
     *
     * @param Schema $schema
     */
    protected function createOroAccountWindowsStateTable(Schema $schema)
    {
        $table = $schema->createTable('oro_acc_windows_state');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('customer_user_id', 'integer', []);
        $table->addColumn('data', Type::JSON_ARRAY, ['comment' => '(DC2Type:json_array)']);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addIndex(['customer_user_id'], 'oro_acc_windows_state_acu_idx', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add oro_category_visibility foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCategoryVisibilityForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_CATEGORY_VISIBILITY_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_CATEGORY_TABLE_NAME),
            ['category_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_acc_category_visibility foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroAccountCategoryVisibilityForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_ACCOUNT_CATEGORY_VISIBILITY_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_CATEGORY_TABLE_NAME),
            ['category_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_ACCOUNT_TABLE_NAME),
            ['account_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_acc_grp_ctgr_visibility foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroAccountGroupCategoryVisibilityForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_ACCOUNT_GROUP_CATEGORY_VISIBILITY_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_CATEGORY_TABLE_NAME),
            ['category_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_ACCOUNT_GROUP_TABLE_NAME),
            ['account_group_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_product_visibility foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroProductVisibilityForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_PRODUCT_VISIBILITY_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_PRODUCT_TABLE_NAME),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_WEBSITE_TABLE_NAME),
            ['website_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_acc_product_visibility foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroAccountProductVisibilityForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_ACCOUNT_PRODUCT_VISIBILITY_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_PRODUCT_TABLE_NAME),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_WEBSITE_TABLE_NAME),
            ['website_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_ACCOUNT_TABLE_NAME),
            ['account_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_acc_grp_prod_visibility foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroAccountGroupProductVisibilityForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_ACCOUNT_GROUP_PRODUCT_VISIBILITY_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_PRODUCT_TABLE_NAME),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_WEBSITE_TABLE_NAME),
            ['website_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_ACCOUNT_GROUP_TABLE_NAME),
            ['account_group_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_acc_windows_state foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroAccountWindowsStateForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_acc_windows_state');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_account_user'),
            ['customer_user_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_prod_vsb_resolv foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroProductVisibilityResolvedForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_PRODUCT_VISIBILITY_RESOLVED);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_website'),
            ['website_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product_visibility'),
            ['source_product_visibility'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_catalog_category'),
            ['category_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_acc_grp_prod_vsb_resolv foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroAccountGroupProductVisibilityResolvedForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_ACCOUNT_GROUP_PRODUCT_VISIBILITY_RESOLVED);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_website'),
            ['website_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_acc_grp_prod_visibility'),
            ['source_product_visibility'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_account_group'),
            ['account_group_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_catalog_category'),
            ['category_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_acc_prod_vsb_resolv foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroAccountProductVisibilityResolvedForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_ACCOUNT_PRODUCT_VISIBILITY_RESOLVED);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_website'),
            ['website_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_acc_product_visibility'),
            ['source_product_visibility'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_account'),
            ['account_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_catalog_category'),
            ['category_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_account_sales_representatives foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroAccountSalesRepresentativesForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_ACCOUNT_SALES_REPRESENTATIVES_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_USER_TABLE_NAME),
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_ACCOUNT_TABLE_NAME),
            ['account_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_account_user_sales_representatives foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroAccountUserSalesRepresentativesForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_ACCOUNT_USER_SALES_REPRESENTATIVES_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_USER_TABLE_NAME),
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_ACCOUNT_USER_TABLE_NAME),
            ['account_user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_ctgr_vsb_resolv foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCategoryVisibilityResolvedForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_CATEGORY_VISIBILITY_RESOLVED);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_category_visibility'),
            ['source_category_visibility'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_catalog_category'),
            ['category_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_acc_grp_ctgr_vsb_resolv foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroAccountGroupCategoryVisibilityResolvedForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_ACCOUNT_GROUP_CATEGORY_VISIBILITY_RESOLVED);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_acc_grp_ctgr_visibility'),
            ['source_category_visibility'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_account_group'),
            ['account_group_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_catalog_category'),
            ['category_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_acc_ctgr_vsb_resolv foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroAccountCategoryVisibilityResolvedForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_ACCOUNT_CATEGORY_VISIBILITY_RESOLVED);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_acc_category_visibility'),
            ['source_category_visibility'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_account'),
            ['account_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_catalog_category'),
            ['category_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * @param Schema $schema
     */
    protected function addOroAccountUserSettingsForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_ACCOUNT_USER_SETTINGS);

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_account_user'),
            ['account_user_id'],
            ['id'],
            ['onDelete' => 'CASCADE'],
            'fk_account_user_id'
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

        $table->addUniqueIndex(['account_user_id', 'website_id'], 'unique_acc_user_website');
    }

    /**
     * @param Schema $schema
     */
    private function addRelationsToScope(Schema $schema)
    {
        $this->extendExtension->addManyToOneRelation(
            $schema,
            OroScopeBundleInstaller::ORO_SCOPE,
            'accountGroup',
            OroAccountBundleInstaller::ORO_ACCOUNT_GROUP_TABLE_NAME,
            'id',
            [
                'extend' => [
                    'owner' => ExtendScope::OWNER_CUSTOM,
                    'cascade' => ['all'],
                    'on_delete' => 'CASCADE',
                    'nullable' => true
                ]
            ],
            RelationType::MANY_TO_ONE
        );

        $this->extendExtension->addManyToOneRelation(
            $schema,
            OroScopeBundleInstaller::ORO_SCOPE,
            'account',
            OroAccountBundleInstaller::ORO_ACCOUNT_TABLE_NAME,
            'id',
            [
                'extend' => [
                    'owner' => ExtendScope::OWNER_CUSTOM,
                    'cascade' => ['all'],
                    'on_delete' => 'CASCADE',
                    'nullable' => true
                ]
            ],
            RelationType::MANY_TO_ONE
        );
    }
}
