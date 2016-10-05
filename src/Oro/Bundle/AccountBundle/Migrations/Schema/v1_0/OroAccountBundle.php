<?php

namespace Oro\Bundle\AccountBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtension;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtension;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtension;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtensionAwareInterface;
use Oro\Bundle\AccountBundle\Entity\Account;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class OroAccountBundle implements
    Migration,
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
    const ORO_B2B_ACCOUNT_USER_ADDRESS_TABLE_NAME = 'orob2b_account_user_address';
    const ORO_B2B_ACC_USR_ADR_TO_ADR_TYPE_TABLE_NAME = 'orob2b_acc_usr_adr_to_adr_type';

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
        $this->createOroAuditFieldTable($schema);
        $this->createOroAuditTable($schema);
        $this->createOroAccountUserAddressTable($schema);
        $this->createOroAccUsrAdrToAdrTypeTable($schema);
        $this->createOroNavigationHistoryTable($schema);
        $this->createOroNavigationItemTable($schema);
        $this->createOroNavigationItemPinbarTable($schema);
        $this->createOroAccountUserSdbarStTable($schema);
        $this->createOroAccountUserSdbarWdgTable($schema);
        $this->createOroAccNavigationPagestateTable($schema);

        /** Foreign keys generation **/
        $this->addOroAccountUserForeignKeys($schema);
        $this->addOroAccountUserAccessAccountUserRoleForeignKeys($schema);
        $this->addOroAccountUserOrganizationForeignKeys($schema);
        $this->addOroAccountUserRoleForeignKeys($schema);
        $this->addOroAccountUserRoleToWebsiteForeignKeys($schema);
        $this->addOroAccountForeignKeys($schema);
        $this->addOroAccountAddressForeignKeys($schema);
        $this->addOroAccountAdrAdrTypeForeignKeys($schema);
        $this->addOroAuditFieldForeignKeys($schema);
        $this->addOroAuditForeignKeys($schema);
        $this->addOroAccountUserAddressForeignKeys($schema);
        $this->addOroAccUsrAdrToAdrTypeForeignKeys($schema);
        $this->addOroNavigationHistoryForeignKeys($schema);
        $this->addOroNavigationItemForeignKeys($schema);
        $this->addOroNavigationItemPinbarForeignKeys($schema);
        $this->addOroAccountUserSdbarStForeignKeys($schema);
        $this->addOroAccountUserSdbarWdgForeignKeys($schema);
        $this->addOroAccNavigationPagestateForeignKeys($schema);
    }

    /**
     * Create orob2b_account_user table
     *
     * @param Schema $schema
     */
    protected function createOroAccountUserTable(Schema $schema)
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
                'application/vnd.openxmlformats-officedocument.presentationml.presentation'
            ]
        );

        $this->noteExtension->addNoteAssociation($schema, static::ORO_B2B_ACCOUNT_USER_TABLE_NAME);

        $this->activityExtension->addActivityAssociation(
            $schema,
            static::ORO_EMAIL,
            static::ORO_B2B_ACCOUNT_USER_TABLE_NAME
        );
    }

    /**
     * Create orob2b_account table
     *
     * @param Schema $schema
     */
    protected function createOroAccountTable(Schema $schema)
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
                'application/vnd.openxmlformats-officedocument.presentationml.presentation'
            ]
        );

        $this->noteExtension->addNoteAssociation($schema, static::ORO_B2B_ACCOUNT_TABLE_NAME);
        $this->extendExtension->addEnumField(
            $schema,
            static::ORO_B2B_ACCOUNT_TABLE_NAME,
            'internal_rating',
            Account::INTERNAL_RATING_CODE
        );
    }

    /**
     * Create orob2b_account_user_access_user_role table
     *
     * @param Schema $schema
     */
    protected function createOroAccountUserAccessAccountUserRoleTable(Schema $schema)
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
    protected function createOroAccountGroupTable(Schema $schema)
    {
        $table = $schema->createTable(static::ORO_B2B_ACCOUNT_GROUP_TABLE_NAME);

        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);

        $table->setPrimaryKey(['id']);

        $table->addIndex(['name'], 'orob2b_account_group_name_idx', []);

        $this->noteExtension->addNoteAssociation($schema, static::ORO_B2B_ACCOUNT_GROUP_TABLE_NAME);
    }

    /**
     * Create orob2b_audit_field table
     *
     * @param Schema $schema
     */
    protected function createOroAuditFieldTable(Schema $schema)
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
        $table->addColumn('visible', 'boolean', ['default' => '1']);
        $table->addColumn('old_datetimetz', 'datetimetz', ['notnull' => false]);
        $table->addColumn('old_object', 'object', ['notnull' => false, 'comment' => '(DC2Type:object)']);
        $table->addColumn('old_array', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->addColumn(
            'old_simplearray',
            'simple_array',
            ['notnull' => false, 'comment' => '(DC2Type:simple_array)']
        );
        $table->addColumn('old_jsonarray', 'json_array', ['notnull' => false]);
        $table->addColumn('new_datetimetz', 'datetimetz', ['notnull' => false]);
        $table->addColumn('new_object', 'object', ['notnull' => false, 'comment' => '(DC2Type:object)']);
        $table->addColumn('new_array', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->addColumn(
            'new_simplearray',
            'simple_array',
            ['notnull' => false, 'comment' => '(DC2Type:simple_array)']
        );
        $table->addColumn('new_jsonarray', 'json_array', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create orob2b_audit table
     *
     * @param Schema $schema
     */
    protected function createOroAuditTable(Schema $schema)
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
     * Create orob2b_account_user_organization table
     *
     * @param Schema $schema
     */
    protected function createOroAccountUserOrganizationTable(Schema $schema)
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
    protected function createOroAccountUserRoleTable(Schema $schema)
    {
        $table = $schema->createTable(static::ORO_B2B_ACCOUNT_USER_ROLE_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_id', 'integer', ['notnull' => false]);
        $table->addColumn('role', 'string', ['length' => 64]);
        $table->addColumn('label', 'string', ['length' => 64]);
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
    protected function createOroAccountUserRoleToWebsiteTable(Schema $schema)
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
    protected function createOroAccountAddressTable(Schema $schema)
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
        $table->addColumn('created', 'datetime', []);
        $table->addColumn('updated', 'datetime', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create orob2b_account_adr_adr_type table
     *
     * @param Schema $schema
     */
    protected function createOroAccountAdrAdrTypeTable(Schema $schema)
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
    protected function createOroNavigationHistoryTable(Schema $schema)
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
    protected function createOroNavigationItemTable(Schema $schema)
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
    protected function createOroNavigationItemPinbarTable(Schema $schema)
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
    protected function createOroAccountUserSdbarStTable(Schema $schema)
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
    protected function createOroAccountUserSdbarWdgTable(Schema $schema)
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
    protected function createOroAccNavigationPagestateTable(Schema $schema)
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
     * Add orob2b_account_user foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroAccountUserForeignKeys(Schema $schema)
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
    protected function addOroAccountUserAccessAccountUserRoleForeignKeys(Schema $schema)
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
    protected function addOroAccountForeignKeys(Schema $schema)
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
    protected function addOroAccountUserOrganizationForeignKeys(Schema $schema)
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
    protected function addOroAccountUserRoleForeignKeys(Schema $schema)
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
    protected function addOroAccountUserRoleToWebsiteForeignKeys(Schema $schema)
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
    protected function addOroAccountAddressForeignKeys(Schema $schema)
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
     * Add orob2b_audit_field foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroAuditFieldForeignKeys(Schema $schema)
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
     * Add orob2b_account_adr_adr_type foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroAccountAdrAdrTypeForeignKeys(Schema $schema)
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
     * Add orob2b_audit foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroAuditForeignKeys(Schema $schema)
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

    /**
     * Create orob2b_account_user_address table
     *
     * @param Schema $schema
     */
    protected function createOroAccountUserAddressTable(Schema $schema)
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
        $table->addColumn('created', 'datetime', []);
        $table->addColumn('updated', 'datetime', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add orob2b_account_user_address foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroAccountUserAddressForeignKeys(Schema $schema)
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
    protected function createOroAccUsrAdrToAdrTypeTable(Schema $schema)
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
    protected function addOroAccUsrAdrToAdrTypeForeignKeys(Schema $schema)
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
    protected function addOroNavigationHistoryForeignKeys(Schema $schema)
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
    protected function addOroNavigationItemForeignKeys(Schema $schema)
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
    protected function addOroNavigationItemPinbarForeignKeys(Schema $schema)
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
    protected function addOroAccountUserSdbarStForeignKeys(Schema $schema)
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
    protected function addOroAccountUserSdbarWdgForeignKeys(Schema $schema)
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
    protected function addOroAccNavigationPagestateForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_acc_pagestate');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account_user'),
            ['account_user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
