<?php

namespace OroB2B\Bundle\AccountBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtension;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtension;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtension;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtensionAwareInterface;

use OroB2B\Bundle\AccountBundle\Entity\Account;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class OroB2BAccountBundleInstaller implements
    Installation,
    AttachmentExtensionAwareInterface,
    NoteExtensionAwareInterface,
    ActivityExtensionAwareInterface,
    ExtendExtensionAwareInterface
{
    const ORO_B2B_ACCOUNT_TABLE_NAME = 'orob2b_account';
    const ORO_B2B_ACCOUNT_USER_TABLE_NAME = 'orob2b_account_user';
    const ORO_B2B_ACC_USER_ACCESS_ROLE_TABLE_NAME = 'orob2b_acc_user_access_role';
    const ORO_B2B_ACCOUNT_GROUP_TABLE_NAME = 'orob2b_account_group';
    const ORO_B2B_ACCOUNT_USER_ORG_TABLE_NAME = 'orob2b_account_user_org';
    const ORO_B2B_ACCOUNT_USER_ROLE_TABLE_NAME = 'orob2b_account_user_role';
    const ORO_B2B_ACCOUNT_ROLE_TO_WEBSITE_TABLE_NAME = 'orob2b_account_role_to_website';
    const ORO_B2B_WEBSITE_TABLE_NAME = 'orob2b_website';
    const ORO_ORGANIZATION_TABLE_NAME = 'oro_organization';
    const ORO_B2B_ACCOUNT_ADDRESS_TABLE_NAME = 'orob2b_account_address';
    const ORO_B2B_ACCOUNT_ADDRESS_TO_ADDRESS_TABLE_NAME = 'orob2b_account_adr_adr_type';
    const ORO_DICTIONARY_REGION_TABLE_NAME = 'oro_dictionary_region';
    const ORO_DICTIONARY_COUNTRY_TABLE_NAME = 'oro_dictionary_country';
    const ORO_ADDRESS_TYPE_TABLE_NAME = 'oro_address_type';
    const ORO_EMAIL = 'oro_email';
    const ORO_CALENDAR_EVENT = 'oro_calendar_event';
    const ORO_B2B_ACCOUNT_USER_ADDRESS_TABLE_NAME = 'orob2b_account_user_address';
    const ORO_B2B_ACC_USR_ADR_TO_ADR_TYPE_TABLE_NAME = 'orob2b_acc_usr_adr_to_adr_type';

    const ORO_B2B_CATEGORY_VISIBILITY_TABLE_NAME = 'orob2b_category_visibility';
    const ORO_B2B_ACCOUNT_CATEGORY_VISIBILITY_TABLE_NAME = 'orob2b_acc_category_visibility';
    const ORO_B2B_ACCOUNT_GROUP_CATEGORY_VISIBILITY_TABLE_NAME = 'orob2b_acc_grp_ctgr_visibility';
    const ORO_B2B_CATEGORY_TABLE_NAME = 'orob2b_catalog_category';

    const ORO_B2B_PRODUCT_VISIBILITY_TABLE_NAME = 'orob2b_product_visibility';
    const ORO_B2B_ACCOUNT_PRODUCT_VISIBILITY_TABLE_NAME = 'orob2b_acc_product_visibility';
    const ORO_B2B_ACCOUNT_GROUP_PRODUCT_VISIBILITY_TABLE_NAME = 'orob2b_acc_grp_prod_visibility';
    const ORO_B2B_PRODUCT_TABLE_NAME = 'orob2b_product';

    const ORO_B2B_PRODUCT_VISIBILITY_RESOLVED = 'orob2b_prod_vsb_resolv';
    const ORO_B2B_ACCOUNT_GROUP_PRODUCT_VISIBILITY_RESOLVED = 'orob2b_acc_grp_prod_vsb_resolv';
    const ORO_B2B_ACCOUNT_PRODUCT_VISIBILITY_RESOLVED = 'orob2b_acc_prod_vsb_resolv';

    const ORO_B2B_CATEGORY_VISIBILITY_RESOLVED = 'orob2b_ctgr_vsb_resolv';
    const ORO_B2B_ACCOUNT_GROUP_CATEGORY_VISIBILITY_RESOLVED = 'orob2b_acc_grp_ctgr_vsb_resolv';
    const ORO_B2B_ACCOUNT_CATEGORY_VISIBILITY_RESOLVED = 'orob2b_acc_ctgr_vsb_resolv';

    const ORO_USER_TABLE_NAME = 'oro_user';
    const ORO_B2B_ACCOUNT_SALES_REPRESENTATIVES_TABLE_NAME = 'orob2b_account_sales_reps';
    const ORO_B2B_ACCOUNT_USER_SALES_REPRESENTATIVES_TABLE_NAME = 'orob2b_account_user_sales_reps';
    const ORO_B2B_ACCOUNT_USER_SETTINGS = 'orob2b_account_user_settings';

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
        return 'v1_6';
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
        $this->createOroB2BAccountUserTable($schema);
        $this->createOroB2BAccountUserOrganizationTable($schema);
        $this->createOroB2BAccountUserRoleTable($schema);
        $this->createOroB2BAccountUserAccessAccountUserRoleTable($schema);
        $this->createOroB2BAccountUserRoleToWebsiteTable($schema);
        $this->createOroB2BAccountTable($schema);
        $this->createOroB2BAccountGroupTable($schema);
        $this->createOroB2BAccountAddressTable($schema);
        $this->createOroB2BAccountAdrAdrTypeTable($schema);
        $this->updateOroAuditTable($schema);
        $this->createOroB2BAccountUserAddressTable($schema);
        $this->createOroB2BAccUsrAdrToAdrTypeTable($schema);
        $this->createOroB2BNavigationHistoryTable($schema);
        $this->createOroB2BNavigationItemTable($schema);
        $this->createOroB2BNavigationItemPinbarTable($schema);
        $this->createOroB2BAccountUserSdbarStTable($schema);
        $this->createOroB2BAccountUserSdbarWdgTable($schema);
        $this->createOroB2BAccNavigationPagestateTable($schema);
        $this->createOroB2BAccountUserSettingsTable($schema);

        $this->createOroB2BCategoryVisibilityTable($schema);
        $this->createOroB2BAccountCategoryVisibilityTable($schema);
        $this->createOroB2BAccountGroupCategoryVisibilityTable($schema);

        $this->createOroB2BProductVisibilityTable($schema);
        $this->createOroB2BAccountProductVisibilityTable($schema);
        $this->createOroB2BAccountGroupProductVisibilityTable($schema);

        $this->createOrob2BWindowsStateTable($schema);
        $this->createOroB2BProductVisibilityResolvedTable($schema);
        $this->createOroB2BAccountGroupProductVisibilityResolvedTable($schema);
        $this->createOroB2BAccountProductVisibilityResolvedTable($schema);

        $this->createOroB2BAccountSalesRepresentativesTable($schema);
        $this->createOroB2BAccountUserSalesRepresentativesTable($schema);

        $this->createOroB2BCategoryVisibilityResolvedTable($schema);
        $this->createOroB2BAccountGroupCategoryVisibilityResolvedTable($schema);
        $this->createOroB2BAccountCategoryVisibilityResolvedTable($schema);

        /** Foreign keys generation **/
        $this->addOroB2BAccountUserForeignKeys($schema);
        $this->addOroB2BAccountUserAccessAccountUserRoleForeignKeys($schema);
        $this->addOroB2BAccountUserOrganizationForeignKeys($schema);
        $this->addOroB2BAccountUserRoleForeignKeys($schema);
        $this->addOroB2BAccountUserRoleToWebsiteForeignKeys($schema);
        $this->addOroB2BAccountForeignKeys($schema);
        $this->addOroB2BAccountAddressForeignKeys($schema);
        $this->addOroB2BAccountAdrAdrTypeForeignKeys($schema);
        $this->addOroB2BAccountUserAddressForeignKeys($schema);
        $this->addOroB2BAccUsrAdrToAdrTypeForeignKeys($schema);
        $this->addOroB2BNavigationHistoryForeignKeys($schema);
        $this->addOroB2BNavigationItemForeignKeys($schema);
        $this->addOroB2BNavigationItemPinbarForeignKeys($schema);
        $this->addOroB2BAccountUserSdbarStForeignKeys($schema);
        $this->addOroB2BAccountUserSdbarWdgForeignKeys($schema);
        $this->addOroB2BAccNavigationPagestateForeignKeys($schema);
        $this->addOroB2BAccountUserSettingsForeignKeys($schema);

        $this->addOroB2BProductVisibilityForeignKeys($schema);
        $this->addOroB2BAccountProductVisibilityForeignKeys($schema);
        $this->addOroB2BAccountGroupProductVisibilityForeignKeys($schema);

        $this->addOrob2BWindowsStateForeignKeys($schema);
        $this->addOroB2BProductVisibilityResolvedForeignKeys($schema);
        $this->addOroB2BAccountGroupProductVisibilityResolvedForeignKeys($schema);
        $this->addOroB2BAccountProductVisibilityResolvedForeignKeys($schema);

        $this->addOroB2BAccountSalesRepresentativesForeignKeys($schema);
        $this->addOroB2BAccountUserSalesRepresentativesForeignKeys($schema);

        $this->addOroB2BCategoryVisibilityForeignKeys($schema);
        $this->addOroB2BAccountCategoryVisibilityForeignKeys($schema);
        $this->addOroB2BAccountGroupCategoryVisibilityForeignKeys($schema);

        $this->addOroB2BCategoryVisibilityResolvedForeignKeys($schema);
        $this->addOroB2BAccountGroupCategoryVisibilityResolvedForeignKeys($schema);
        $this->addOroB2BAccountCategoryVisibilityResolvedForeignKeys($schema);
    }

    /**
     * Create orob2b_account_user table
     *
     * @param Schema $schema
     */
    protected function createOroB2BAccountUserTable(Schema $schema)
    {
        $table = $schema->createTable(static::ORO_B2B_ACCOUNT_USER_TABLE_NAME);

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

        $table->setPrimaryKey(['id']);

        $table->addUniqueIndex(['username']);
        $table->addUniqueIndex(['email']);

        $this->attachmentExtension->addAttachmentAssociation(
            $schema,
            static::ORO_B2B_ACCOUNT_USER_TABLE_NAME,
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

        $this->noteExtension->addNoteAssociation($schema, static::ORO_B2B_ACCOUNT_USER_TABLE_NAME);

        $this->activityExtension->addActivityAssociation(
            $schema,
            static::ORO_EMAIL,
            static::ORO_B2B_ACCOUNT_USER_TABLE_NAME
        );
        $this->activityExtension->addActivityAssociation(
            $schema,
            static::ORO_CALENDAR_EVENT,
            static::ORO_B2B_ACCOUNT_USER_TABLE_NAME
        );
    }

    /**
     * Create orob2b_account table
     *
     * @param Schema $schema
     */
    protected function createOroB2BAccountTable(Schema $schema)
    {
        $table = $schema->createTable(static::ORO_B2B_ACCOUNT_TABLE_NAME);

        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('parent_id', 'integer', ['notnull' => false]);
        $table->addColumn('group_id', 'integer', ['notnull' => false]);
        $table->addColumn('owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);

        $table->setPrimaryKey(['id']);

        $table->addIndex(['name'], 'orob2b_account_name_idx', []);

        $this->attachmentExtension->addAttachmentAssociation(
            $schema,
            static::ORO_B2B_ACCOUNT_TABLE_NAME,
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

        $this->noteExtension->addNoteAssociation($schema, static::ORO_B2B_ACCOUNT_TABLE_NAME);
        $this->extendExtension->addEnumField(
            $schema,
            static::ORO_B2B_ACCOUNT_TABLE_NAME,
            'internal_rating',
            Account::INTERNAL_RATING_CODE,
            false,
            false,
            ['dataaudit' => ['auditable' => true]]
        );
    }

    /**
     * Create orob2b_account_user_access_user_role table
     *
     * @param Schema $schema
     */
    protected function createOroB2BAccountUserAccessAccountUserRoleTable(Schema $schema)
    {
        $table = $schema->createTable(static::ORO_B2B_ACC_USER_ACCESS_ROLE_TABLE_NAME);

        $table->addColumn('account_user_id', 'integer', []);
        $table->addColumn('account_user_role_id', 'integer', []);

        $table->setPrimaryKey(['account_user_id', 'account_user_role_id']);
    }

    /**
     * Create orob2b_account_group table
     *
     * @param Schema $schema
     */
    protected function createOroB2BAccountGroupTable(Schema $schema)
    {
        $table = $schema->createTable(static::ORO_B2B_ACCOUNT_GROUP_TABLE_NAME);

        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);

        $table->setPrimaryKey(['id']);

        $table->addIndex(['name'], 'orob2b_account_group_name_idx', []);

        $this->noteExtension->addNoteAssociation($schema, static::ORO_B2B_ACCOUNT_GROUP_TABLE_NAME);
    }

    /**
     * Create orob2b_audit table
     *
     * @param Schema $schema
     */
    protected function updateOroAuditTable(Schema $schema)
    {
        $auditTable = $schema->getTable('oro_audit');
        $auditTable->addColumn('account_user_id', 'integer', ['notnull' => false]);

        $auditTable->addForeignKeyConstraint(
            $schema->getTable('orob2b_account_user'),
            ['account_user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Create orob2b_account_user_organization table
     *
     * @param Schema $schema
     */
    protected function createOroB2BAccountUserOrganizationTable(Schema $schema)
    {
        $table = $schema->createTable(static::ORO_B2B_ACCOUNT_USER_ORG_TABLE_NAME);

        $table->addColumn('account_user_id', 'integer', []);
        $table->addColumn('organization_id', 'integer', []);

        $table->setPrimaryKey(['account_user_id', 'organization_id']);
    }

    /**
     * Create orob2b_account_user_role table
     *
     * @param Schema $schema
     */
    protected function createOroB2BAccountUserRoleTable(Schema $schema)
    {
        $table = $schema->createTable(static::ORO_B2B_ACCOUNT_USER_ROLE_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_id', 'integer', ['notnull' => false]);
        $table->addColumn('role', 'string', ['length' => 255]);
        $table->addColumn('label', 'string', ['length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['role']);
        $table->addUniqueIndex(['account_id', 'label'], 'orob2b_account_user_role_account_id_label_idx');

        $this->noteExtension->addNoteAssociation($schema, static::ORO_B2B_ACCOUNT_USER_ROLE_TABLE_NAME);
    }

    /**
     * Create orob2b_account_role_to_website table
     *
     * @param Schema $schema
     */
    protected function createOroB2BAccountUserRoleToWebsiteTable(Schema $schema)
    {
        $table = $schema->createTable(static::ORO_B2B_ACCOUNT_ROLE_TO_WEBSITE_TABLE_NAME);
        $table->addColumn('account_user_role_id', 'integer', []);
        $table->addColumn('website_id', 'integer', []);
        $table->setPrimaryKey(['account_user_role_id', 'website_id']);
        $table->addUniqueIndex(['website_id']);
    }

    /**
     * Create orob2b_account_address table
     *
     * @param Schema $schema
     */
    protected function createOroB2BAccountAddressTable(Schema $schema)
    {
        $table = $schema->createTable(static::ORO_B2B_ACCOUNT_ADDRESS_TABLE_NAME);
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
     * Create orob2b_account_adr_adr_type table
     *
     * @param Schema $schema
     */
    protected function createOroB2BAccountAdrAdrTypeTable(Schema $schema)
    {
        $table = $schema->createTable(static::ORO_B2B_ACCOUNT_ADDRESS_TO_ADDRESS_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('type_name', 'string', ['notnull' => false, 'length' => 16]);
        $table->addColumn('account_address_id', 'integer', ['notnull' => false]);
        $table->addColumn('is_default', 'boolean', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['account_address_id', 'type_name'], 'orob2b_account_adr_id_type_name_idx');
    }

    /**
     * Create orob2b_navigation_history table
     *
     * @param Schema $schema
     */
    protected function createOroB2BNavigationHistoryTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_acc_navigation_history');
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
        $table->addIndex(['route'], 'orob2b_navigation_history_route_idx');
        $table->addIndex(['entity_id'], 'orob2b_navigation_history_entity_id_idx');
    }

    /**
     * Create orob2b_navigation_item table
     *
     * @param Schema $schema
     */
    protected function createOroB2BNavigationItemTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_acc_navigation_item');
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
        $table->addIndex(['account_user_id', 'position'], 'oro_b2b_sorted_items_idx', []);
    }

    /**
     * Create orob2b_acc_nav_item_pinbar table
     *
     * @param Schema $schema
     */
    protected function createOroB2BNavigationItemPinbarTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_acc_nav_item_pinbar');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('item_id', 'integer', []);
        $table->addColumn('maximized', 'datetime', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['item_id'], 'UNIQ_F6DC70B5126F525E');
    }

    /**
     * Create orob2b_account_user_sdbar_st table
     *
     * @param Schema $schema
     */
    protected function createOroB2BAccountUserSdbarStTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_account_user_sdbar_st');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('account_user_id', 'integer', []);
        $table->addColumn('position', 'string', ['length' => 13]);
        $table->addColumn('state', 'string', ['length' => 17]);
        $table->addUniqueIndex(['account_user_id', 'position'], 'b2b_sdbar_st_unq_idx');
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create orob2b_account_user_sdbar_wdg table
     *
     * @param Schema $schema
     */
    protected function createOroB2BAccountUserSdbarWdgTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_account_user_sdbar_wdg');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_user_id', 'integer', []);
        $table->addColumn('placement', 'string', ['length' => 50]);
        $table->addColumn('position', 'smallint', []);
        $table->addColumn('widget_name', 'string', ['length' => 50]);
        $table->addColumn('settings', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->addColumn('state', 'string', ['length' => 22]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['position'], 'b2b_sdar_wdgs_pos_idx', []);
        $table->addIndex(['account_user_id', 'placement'], 'b2b_sdbr_wdgs_usr_place_idx', []);
    }

    /**
     * Create orob2b_acc_pagestate table
     *
     * @param Schema $schema
     */
    protected function createOroB2BAccNavigationPagestateTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_acc_pagestate');
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
     * Create orob2b_prod_vsb_resolv table
     *
     * @param Schema $schema
     */
    protected function createOroB2BProductVisibilityResolvedTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_B2B_PRODUCT_VISIBILITY_RESOLVED);
        $table->addColumn('website_id', 'integer', []);
        $table->addColumn('product_id', 'integer', []);
        $table->addColumn('source_product_visibility', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'smallint', ['notnull' => false]);
        $table->addColumn('source', 'smallint', ['notnull' => false]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['website_id', 'product_id']);
    }

    /**
     * Create orob2b_acc_grp_prod_vsb_resolv table
     *
     * @param Schema $schema
     */
    protected function createOroB2BAccountGroupProductVisibilityResolvedTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_B2B_ACCOUNT_GROUP_PRODUCT_VISIBILITY_RESOLVED);
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
     * Create orob2b_acc_prod_vsb_resolv table
     *
     * @param Schema $schema
     */
    protected function createOroB2BAccountProductVisibilityResolvedTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_B2B_ACCOUNT_PRODUCT_VISIBILITY_RESOLVED);
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
     * Create orob2b_account_sales_representatives table
     *
     * @param Schema $schema
     */
    protected function createOroB2BAccountSalesRepresentativesTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_B2B_ACCOUNT_SALES_REPRESENTATIVES_TABLE_NAME);
        $table->addColumn('account_id', 'integer');
        $table->addColumn('user_id', 'integer');
        $table->setPrimaryKey(['account_id', 'user_id']);
    }

    /**
     * Create orob2b_account_user_sales_representatives table
     *
     * @param Schema $schema
     */
    protected function createOroB2BAccountUserSalesRepresentativesTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_B2B_ACCOUNT_USER_SALES_REPRESENTATIVES_TABLE_NAME);
        $table->addColumn('account_user_id', 'integer');
        $table->addColumn('user_id', 'integer');
        $table->setPrimaryKey(['account_user_id', 'user_id']);
    }

    /**
     * Create orob2b_ctgr_vsb_resolv table
     *
     * @param Schema $schema
     */
    protected function createOroB2BCategoryVisibilityResolvedTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_B2B_CATEGORY_VISIBILITY_RESOLVED);
        $table->addColumn('source_category_visibility', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'smallint', ['notnull' => false]);
        $table->addColumn('source', 'smallint', ['notnull' => false]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['category_id']);
    }

    /**
     * Create orob2b_acc_grp_ctgr_vsb_resolv table
     *
     * @param Schema $schema
     */
    protected function createOroB2BAccountGroupCategoryVisibilityResolvedTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_B2B_ACCOUNT_GROUP_CATEGORY_VISIBILITY_RESOLVED);
        $table->addColumn('account_group_id', 'integer', []);
        $table->addColumn('source_category_visibility', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'smallint', ['notnull' => false]);
        $table->addColumn('source', 'smallint', ['notnull' => false]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['account_group_id', 'category_id']);
    }

    /**
     * Create orob2b_acc_ctgr_vsb_resolv table
     *
     * @param Schema $schema
     */
    protected function createOroB2BAccountCategoryVisibilityResolvedTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_B2B_ACCOUNT_CATEGORY_VISIBILITY_RESOLVED);
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
    protected function createOroB2BAccountUserSettingsTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_B2B_ACCOUNT_USER_SETTINGS);

        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('account_user_id', 'integer');
        $table->addColumn('website_id', 'integer');
        $table->addColumn('currency', 'string', ['length' => 3, 'notnull' => false]);
        $table->addColumn('localization_id', 'integer', ['notnull' => false]);

        $table->setPrimaryKey(['id']);
    }

    /**
     * Add orob2b_account_user foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BAccountUserForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(static::ORO_B2B_ACCOUNT_USER_TABLE_NAME);
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
            $schema->getTable(static::ORO_B2B_ACCOUNT_TABLE_NAME),
            ['account_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_account_user_access_user_role foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BAccountUserAccessAccountUserRoleForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(static::ORO_B2B_ACC_USER_ACCESS_ROLE_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(static::ORO_B2B_ACCOUNT_USER_ROLE_TABLE_NAME),
            ['account_user_role_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(static::ORO_B2B_ACCOUNT_USER_TABLE_NAME),
            ['account_user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_account foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BAccountForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(static::ORO_B2B_ACCOUNT_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(static::ORO_B2B_ACCOUNT_GROUP_TABLE_NAME),
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
     * Add orob2b_account_user_organization foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BAccountUserOrganizationForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(static::ORO_B2B_ACCOUNT_USER_ORG_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(static::ORO_B2B_ACCOUNT_USER_TABLE_NAME),
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
     * Add orob2b_account_user_role foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BAccountUserRoleForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(static::ORO_B2B_ACCOUNT_USER_ROLE_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(static::ORO_ORGANIZATION_TABLE_NAME),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(static::ORO_B2B_ACCOUNT_TABLE_NAME),
            ['account_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_account_role_to_website foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BAccountUserRoleToWebsiteForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(static::ORO_B2B_ACCOUNT_ROLE_TO_WEBSITE_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(static::ORO_B2B_WEBSITE_TABLE_NAME),
            ['website_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(static::ORO_B2B_ACCOUNT_USER_ROLE_TABLE_NAME),
            ['account_user_role_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_account_address foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BAccountAddressForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(static::ORO_B2B_ACCOUNT_ADDRESS_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(static::ORO_B2B_ACCOUNT_TABLE_NAME),
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
     * Add orob2b_account_adr_adr_type foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BAccountAdrAdrTypeForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(static::ORO_B2B_ACCOUNT_ADDRESS_TO_ADDRESS_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(static::ORO_ADDRESS_TYPE_TABLE_NAME),
            ['type_name'],
            ['name'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(static::ORO_B2B_ACCOUNT_ADDRESS_TABLE_NAME),
            ['account_address_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Create orob2b_account_user_address table
     *
     * @param Schema $schema
     */
    protected function createOroB2BAccountUserAddressTable(Schema $schema)
    {
        $table = $schema->createTable(static::ORO_B2B_ACCOUNT_USER_ADDRESS_TABLE_NAME);
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
     * Add orob2b_account_user_address foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BAccountUserAddressForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(static::ORO_B2B_ACCOUNT_USER_ADDRESS_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(static::ORO_DICTIONARY_REGION_TABLE_NAME),
            ['region_code'],
            ['combined_code'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(static::ORO_B2B_ACCOUNT_USER_TABLE_NAME),
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
     * Create orob2b_account_adr_to_adr_type table
     *
     * @param Schema $schema
     */
    protected function createOroB2BAccUsrAdrToAdrTypeTable(Schema $schema)
    {
        $table = $schema->createTable(static::ORO_B2B_ACC_USR_ADR_TO_ADR_TYPE_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('type_name', 'string', ['notnull' => false, 'length' => 16]);
        $table->addColumn('account_user_address_id', 'integer', ['notnull' => false]);
        $table->addColumn('is_default', 'boolean', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['account_user_address_id', 'type_name'], 'orob2b_account_user_adr_id_type_name_idx');
    }

    /**
     * Add orob2b_account_adr_to_adr_type foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BAccUsrAdrToAdrTypeForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(static::ORO_B2B_ACC_USR_ADR_TO_ADR_TYPE_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(static::ORO_ADDRESS_TYPE_TABLE_NAME),
            ['type_name'],
            ['name'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(static::ORO_B2B_ACCOUNT_USER_ADDRESS_TABLE_NAME),
            ['account_user_address_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_navigation_history foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BNavigationHistoryForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_acc_navigation_history');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account_user'),
            ['account_user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_navigation_item foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BNavigationItemForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_acc_navigation_item');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account_user'),
            ['account_user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_acc_nav_item_pinbar foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BNavigationItemPinbarForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_acc_nav_item_pinbar');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_acc_navigation_item'),
            ['item_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_account_user_sdbar_st foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BAccountUserSdbarStForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_account_user_sdbar_st');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account_user'),
            ['account_user_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add orob2b_account_user_sdbar_wdg foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BAccountUserSdbarWdgForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_account_user_sdbar_wdg');
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

    /**
     * Add orob2b_acc_navigation_pagestate foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BAccNavigationPagestateForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_acc_pagestate');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account_user'),
            ['account_user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Create orob2b_category_visibility table
     *
     * @param Schema $schema
     */
    protected function createOroB2BCategoryVisibilityTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_B2B_CATEGORY_VISIBILITY_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'string', ['length' => 255, 'notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['category_id'], 'orob2b_ctgr_vis_uidx');
    }

    /**
     * Create orob2b_acc_category_visibility table
     *
     * @param Schema $schema
     */
    protected function createOroB2BAccountCategoryVisibilityTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_B2B_ACCOUNT_CATEGORY_VISIBILITY_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_id', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'string', ['length' => 255, 'notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['category_id', 'account_id'], 'orob2b_acc_ctgr_vis_uidx');
    }

    /**
     * Create orob2b_acc_grp_ctgr_visibility table
     *
     * @param Schema $schema
     */
    protected function createOroB2BAccountGroupCategoryVisibilityTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_B2B_ACCOUNT_GROUP_CATEGORY_VISIBILITY_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_group_id', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'string', ['length' => 255, 'notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['category_id', 'account_group_id'], 'orob2b_acc_grp_ctgr_vis_uidx');
    }

    /**
     * Create orob2b_product_visibility table
     *
     * @param Schema $schema
     */
    protected function createOroB2BProductVisibilityTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_B2B_PRODUCT_VISIBILITY_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => false]);
        $table->addColumn('website_id', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'string', ['length' => 255, 'notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['website_id', 'product_id'], 'orob2b_prod_vis_uidx');
    }

    /**
     * Create orob2b_acc_product_visibility table
     *
     * @param Schema $schema
     */
    protected function createOroB2BAccountProductVisibilityTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_B2B_ACCOUNT_PRODUCT_VISIBILITY_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => false]);
        $table->addColumn('website_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_id', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'string', ['length' => 255, 'notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['website_id', 'product_id', 'account_id'], 'orob2b_acc_prod_vis_uidx');
    }

    /**
     * Create orob2b_acc_grp_prod_visibility table
     *
     * @param Schema $schema
     */
    protected function createOroB2BAccountGroupProductVisibilityTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_B2B_ACCOUNT_GROUP_PRODUCT_VISIBILITY_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => false]);
        $table->addColumn('website_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_group_id', 'integer', ['notnull' => false]);
        $table->addColumn('visibility', 'string', ['length' => 255, 'notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['website_id', 'product_id', 'account_group_id'], 'orob2b_acc_grp_prod_vis_uidx');
    }

    /**
     * Create orob2b_windows_state table
     *
     * @param Schema $schema
     */
    protected function createOrob2BWindowsStateTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_windows_state');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('customer_user_id', 'integer', []);
        $table->addColumn('data', Type::JSON_ARRAY, ['comment' => '(DC2Type:json_array)']);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addIndex(['customer_user_id'], 'orob2b_windows_state_acu_idx', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add orob2b_category_visibility foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BCategoryVisibilityForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_CATEGORY_VISIBILITY_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_B2B_CATEGORY_TABLE_NAME),
            ['category_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_acc_category_visibility foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BAccountCategoryVisibilityForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_ACCOUNT_CATEGORY_VISIBILITY_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_B2B_CATEGORY_TABLE_NAME),
            ['category_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_B2B_ACCOUNT_TABLE_NAME),
            ['account_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_acc_grp_ctgr_visibility foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BAccountGroupCategoryVisibilityForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_ACCOUNT_GROUP_CATEGORY_VISIBILITY_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_B2B_CATEGORY_TABLE_NAME),
            ['category_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_B2B_ACCOUNT_GROUP_TABLE_NAME),
            ['account_group_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_product_visibility foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BProductVisibilityForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_PRODUCT_VISIBILITY_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_B2B_PRODUCT_TABLE_NAME),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_B2B_WEBSITE_TABLE_NAME),
            ['website_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_acc_product_visibility foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BAccountProductVisibilityForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_ACCOUNT_PRODUCT_VISIBILITY_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_B2B_PRODUCT_TABLE_NAME),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_B2B_WEBSITE_TABLE_NAME),
            ['website_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_B2B_ACCOUNT_TABLE_NAME),
            ['account_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_acc_grp_prod_visibility foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BAccountGroupProductVisibilityForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_ACCOUNT_GROUP_PRODUCT_VISIBILITY_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_B2B_PRODUCT_TABLE_NAME),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_B2B_WEBSITE_TABLE_NAME),
            ['website_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_B2B_ACCOUNT_GROUP_TABLE_NAME),
            ['account_group_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_windows_state foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BWindowsStateForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_windows_state');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account_user'),
            ['customer_user_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add orob2b_prod_vsb_resolv foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BProductVisibilityResolvedForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_PRODUCT_VISIBILITY_RESOLVED);
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_website'),
            ['website_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product_visibility'),
            ['source_product_visibility'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_catalog_category'),
            ['category_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_acc_grp_prod_vsb_resolv foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BAccountGroupProductVisibilityResolvedForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_ACCOUNT_GROUP_PRODUCT_VISIBILITY_RESOLVED);
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_website'),
            ['website_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_acc_grp_prod_visibility'),
            ['source_product_visibility'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account_group'),
            ['account_group_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_catalog_category'),
            ['category_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_acc_prod_vsb_resolv foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BAccountProductVisibilityResolvedForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_ACCOUNT_PRODUCT_VISIBILITY_RESOLVED);
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_website'),
            ['website_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_acc_product_visibility'),
            ['source_product_visibility'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account'),
            ['account_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_catalog_category'),
            ['category_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_account_sales_representatives foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BAccountSalesRepresentativesForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_ACCOUNT_SALES_REPRESENTATIVES_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_USER_TABLE_NAME),
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_B2B_ACCOUNT_TABLE_NAME),
            ['account_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_account_user_sales_representatives foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BAccountUserSalesRepresentativesForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_ACCOUNT_USER_SALES_REPRESENTATIVES_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_USER_TABLE_NAME),
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_B2B_ACCOUNT_USER_TABLE_NAME),
            ['account_user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_ctgr_vsb_resolv foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BCategoryVisibilityResolvedForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_CATEGORY_VISIBILITY_RESOLVED);
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_category_visibility'),
            ['source_category_visibility'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_catalog_category'),
            ['category_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_acc_grp_ctgr_vsb_resolv foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BAccountGroupCategoryVisibilityResolvedForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_ACCOUNT_GROUP_CATEGORY_VISIBILITY_RESOLVED);
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_acc_grp_ctgr_visibility'),
            ['source_category_visibility'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account_group'),
            ['account_group_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_catalog_category'),
            ['category_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_acc_ctgr_vsb_resolv foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BAccountCategoryVisibilityResolvedForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_ACCOUNT_CATEGORY_VISIBILITY_RESOLVED);
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_acc_category_visibility'),
            ['source_category_visibility'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account'),
            ['account_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_catalog_category'),
            ['category_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * @param Schema $schema
     */
    protected function addOroB2BAccountUserSettingsForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_ACCOUNT_USER_SETTINGS);

        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account_user'),
            ['account_user_id'],
            ['id'],
            ['onDelete' => 'CASCADE'],
            'fk_account_user_id'
        );

        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_website'),
            ['website_id'],
            ['id'],
            ['onDelete' => 'CASCADE'],
            'fk_website_id'
        );

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_localization'),
            ['localization_id'],
            ['id'],
            ['onDelete' => 'CASCADE'],
            'fk_localization_id'
        );

        $table->addUniqueIndex(['account_user_id', 'website_id'], 'unique_acc_user_website');
    }
}
