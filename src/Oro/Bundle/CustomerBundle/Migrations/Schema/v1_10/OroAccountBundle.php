<?php

namespace Oro\Bundle\CustomerBundle\Migrations\Schema\v1_10;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\FrontendBundle\Migration\UpdateExtendRelationTrait;
use Oro\Bundle\InstallerBundle\Migration\UpdateTableFieldQuery;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\MigrationConstraintTrait;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class OroAccountBundle implements
    Migration,
    RenameExtensionAwareInterface,
    OrderedMigrationInterface,
    ContainerAwareInterface
{
    use MigrationConstraintTrait,
        UpdateExtendRelationTrait,
        ContainerAwareTrait;
    /**
     * @var RenameExtension
     */
    private $renameExtension;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->renameCustomer($schema, $queries);
        $this->renameCustomerUser($schema, $queries);
        $this->renameCustomerUserSidebarWidget($schema, $queries);
        $this->renameAccountUserSidebarState($schema, $queries);
        $this->renameCustomerSettings($schema, $queries);
        $this->renameAccountUserAddressToAddressType($schema, $queries);
        $this->renameAccountAdrAdrTypeTable($schema, $queries);
        $this->renameAccountUserAddressTable($schema, $queries);
        $this->renameAccountAddressTable($schema, $queries);
        $this->renameAccWindowsStateTable($schema, $queries);
        $this->renameAccNavItemPinbarTable($schema, $queries);
        $this->renameCustomerUserRole($schema, $queries);
        $this->renameCustomerGroup($schema, $queries);
        $this->renameLoadedFixtures($queries);
        $this->updateEntityConfigAcl();

        $configManager = $this->container->get('oro_entity_config.config_manager');
        $registry = $this->container->get('doctrine');
        $migration = new ConfigMigration($registry, $configManager);
        $migration->migrate(
            'Oro\Bundle\CustomerBundle\Entity\CustomerUserRole',
            'accountuserrole',
            'customeruserrole'
        );
        $migration->migrate(
            'Oro\Bundle\ActivityListBundle\Entity\ActivityList',
            'accountuserrole',
            'customeruserrole'
        );
        $migration->migrate(
            'Oro\Bundle\NoteBundle\Entity\Note',
            'accountuserrole',
            'customeruserrole'
        );
        $this->alterScopes($schema, $queries);
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     */
    private function renameCustomerUserSidebarWidget(Schema $schema, QueryBag $queries)
    {

        $table = $schema->getTable("oro_account_user_sdbar_wdg");

        $table->dropIndex("oro_acc_sdbr_wdgs_usr_place_idx");
        $table->dropIndex("oro_acc_sdar_wdgs_pos_idx");

        $fk = $this->getConstraintName($table, 'account_user_id');
        $table->removeForeignKey($fk);
        $this->renameExtension->renameColumn($schema, $queries, $table, "account_user_id", "customer_user_id");

        $this->renameExtension->renameTable(
            $schema,
            $queries,
            "oro_account_user_sdbar_wdg",
            "oro_customer_user_sdbar_wdg"
        );
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     */
    private function renameAccountUserSidebarState(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable("oro_account_user_sdbar_st");

        $table->dropIndex("oro_acc_sdbar_st_unq_idx");
        $this->renameAccountUserId($schema, $queries, "oro_account_user_sdbar_st");
        $this->renameExtension->renameTable(
            $schema,
            $queries,
            "oro_account_user_sdbar_st",
            "oro_customer_user_sdbar_st"
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @param Schema $schema
     * @param QueryBag $queries
     */
    private function renameCustomer(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_account');
        $queries->addQuery(new UpdateTableFieldQuery(
            'oro_account',
            'name',
            'AccountUser AccountUser',
            'CustomerUser CustomerUser'
        ));
        $table->dropIndex('oro_account_name_idx');

        $this->renameAccountId($schema, $queries, 'oro_account_user');

        $this->renameAccountId($schema, $queries, 'oro_account_user_role');

        $this->renameAccountId($schema, $queries, 'oro_account_sales_reps');
        $this->renameExtension->renameTable($schema, $queries, 'oro_account_sales_reps', 'oro_customer_sales_reps');

        $this->renameExtension->renameTable($schema, $queries, 'oro_account', 'oro_customer');

        $configManager = $this->container->get('oro_entity_config.config_manager');
        $queries->addQuery(new UpdateTableFieldQuery(
            'oro_email_template',
            'entityName',
            'Account',
            'Customer'
        ));

        $possibleNoteRelation = [
            'account_8d1f63b9',
            'account_557018f',
            'account_809c1e34',
        ];
        foreach ($possibleNoteRelation as $relation) {
            if ($schema->getTable('oro_note')->hasColumn($relation.'_id')) {
                $table = $schema->getTable('oro_note');
                $fk = $this->getConstraintName($table, $relation.'_id');
                $table->removeForeignKey($fk);
                $table->dropColumn($relation.'_id');

                $query = new UpdateNoteAssociationQuery($schema);
                $query->setTargetClass('Customer');
                $query->setFieldName($relation);
                $queries->addPostQuery($query);
            }
        }

        $possibleTableNames = [
            'oro_rel_c3990ba6b28b6f382b5af2',
            'oro_rel_c3990ba6b28b6f383f1392',
        ];
        foreach ($possibleTableNames as $tableName) {
            if ($schema->hasTable($tableName)) {
                $table = $schema->getTable($tableName);
                $table->removeForeignKey($this->getConstraintName($table, "account_id"));
                foreach ($table->getIndexes() as $index) {
                    if ($index->getColumns() === ['account_id']) {
                        $table->dropIndex($index->getName());
                    }
                }
                $this->renameExtension->renameColumn(
                    $schema,
                    $queries,
                    $table,
                    'account_id',
                    'customer_id'
                );
                $this->renameExtension->renameTable(
                    $schema,
                    $queries,
                    $tableName,
                    'oro_rel_c3990ba6784fec5f6e321b'
                );
            }
        }
        if ($schema->hasTable('oro_rel_6f8f552ab28b6f38cd148c')) {
            $table = $schema->getTable('oro_rel_6f8f552ab28b6f38cd148c');
            $table->removeForeignKey($this->getConstraintName($table, "account_id"));
            $this->renameExtension->renameColumn($schema, $queries, $table, 'account_id', 'customer_id');
            $this->renameExtension->renameTable(
                $schema,
                $queries,
                'oro_rel_6f8f552ab28b6f38cd148c',
                'oro_rel_6f8f552a784fec5fcd148c'
            );
        }

        $possibleActivityRelations = [
            'account_32ea2fb3',
            'account_80d25b4b'
        ];
        foreach ($possibleActivityRelations as $relation) {
            $this->migrateConfig(
                $configManager,
                'Oro\Bundle\ActivityListBundle\Entity\ActivityList',
                'Oro\Bundle\CustomerBundle\Entity\Customer',
                $relation,
                'customer_2a5d7b7',
                RelationType::MANY_TO_MANY
            );
        }

        $registry = $this->container->get('doctrine');
        $migration = new ConfigMigration($registry, $configManager);
        $migration->migrate(
            'Oro\Bundle\CustomerBundle\Entity\Customer',
            '.account',
            '.customer'
        );
        $migration->migrate(
            'Oro\Bundle\ActivityListBundle\Entity\ActivityList',
            '.account',
            '.customer'
        );
        $migration->migrate(
            'Oro\Bundle\NoteBundle\Entity\Note',
            '.account',
            '.customer'
        );
        $migration->migrate(
            'Oro\Bundle\CustomerBundle\Entity\Customer',
            '_Account',
            '_Customer'
        );

        $this->migrateConfig(
            $configManager,
            'Oro\Bundle\AttachmentBundle\Entity\Attachment',
            'Oro\Bundle\CustomerBundle\Entity\Customer',
            'account_8d1f63b9',
            'customer_e2cfcbe5',
            RelationType::MANY_TO_ONE
        );
        $this->migrateConfig(
            $configManager,
            'Oro\Bundle\AttachmentBundle\Entity\Attachment',
            'Oro\Bundle\CustomerBundle\Entity\Customer',
            'account_557018f',
            'customer_e2cfcbe5',
            RelationType::MANY_TO_ONE
        );

        $migration->migrate(
            'Oro\Bundle\AttachmentBundle\Entity\Attachment',
            '.account',
            '.customer'
        );
        $table = $schema->getTable('oro_attachment');
        $table->removeForeignKey($this->getConstraintName($table, 'account_8d1f63b9_id'));
        $this->renameExtension->renameColumn($schema, $queries, $table, 'account_8d1f63b9_id', 'customer_e2cfcbe5_id');
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @param Schema $schema
     * @param QueryBag $queries
     */
    private function renameCustomerUserRole(Schema $schema, QueryBag $queries)
    {
        $configManager = $this->container->get('oro_entity_config.config_manager');
        if ($schema->hasTable('oro_rel_c3990ba69df6f4d830531c')
            && !$schema->getTable('oro_note')->hasColumn('account_user_role_604160ea_id')
        ) {
            $this->migrateConfig(
                $configManager,
                'Oro\Bundle\NoteBundle\Entity\Note',
                'Oro\Bundle\CustomerBundle\Entity\CustomerUserRole',
                'account_user_role_4574e3cd',
                'customer_user_role_baff6731',
                RelationType::MANY_TO_MANY
            );
        } else {
            $possibleNoteRelation = [
                'account_user_role_604160ea',
                'account_user_role_abeddea9',
                'account_user_role_4574e3cd',
            ];
            $tableNote = $schema->getTable('oro_note');
            foreach ($possibleNoteRelation as $relation) {
                if ($tableNote->hasColumn($relation.'_id')) {
                    $fk = $this->getConstraintName($tableNote, $relation.'_id');
                    $tableNote->removeForeignKey($fk);
                    $tableNote->dropColumn($relation.'_id');
                    $query = new UpdateNoteAssociationQuery($schema);
                    $query->setTargetClass('CustomerUserRole');
                    $query->setFieldName($relation);
                    $queries->addPostQuery($query);
                }
            }
        }

        $possibleTableNames = [
            'oro_rel_c3990ba69df6f4d830531c',
            'oro_rel_c3990ba69df6f4d8894a76',
            'oro_rel_c3990ba69df6f4d84415b1',
        ];
        foreach ($possibleTableNames as $tableName) {
            if ($schema->hasTable($tableName)) {
                $table = $schema->getTable($tableName);
                $table->removeForeignKey($this->getConstraintName($table, "accountuserrole_id"));
                foreach ($table->getIndexes() as $index) {
                    if ($index->getColumns() === ['accountuserrole_id']) {
                        $table->dropIndex($index->getName());
                    }
                }
                $this->renameExtension->renameColumn(
                    $schema,
                    $queries,
                    $table,
                    'accountuserrole_id',
                    'customeruserrole_id'
                );
                $this->renameExtension->renameTable(
                    $schema,
                    $queries,
                    $tableName,
                    'oro_rel_c3990ba6d7fa01cd30d950'
                );
            }
        }

        $possibleActivityRelations = [
            'account_user_role_a5e27440',
            'account_user_role_b2c2ca11',
            'account_user_role_13655133',
        ];
        foreach ($possibleActivityRelations as $relation) {
            $this->migrateConfig(
                $configManager,
                'Oro\Bundle\ActivityListBundle\Entity\ActivityList',
                'Oro\Bundle\CustomerBundle\Entity\CustomerUserRole',
                $relation,
                'customer_user_role_29160e3b',
                RelationType::MANY_TO_MANY
            );
        }

        $table = $schema->getTable("oro_acc_user_access_role");
        $this->renameAccountUserId($schema, $queries, "oro_acc_user_access_role");
        $table->removeForeignKey($this->getConstraintName($table, "account_user_role_id"));
        $this->renameExtension->renameColumn(
            $schema,
            $queries,
            $table,
            "account_user_role_id",
            "customer_user_role_id"
        );
        $this->renameExtension->renameTable(
            $schema,
            $queries,
            "oro_acc_user_access_role",
            "oro_cus_user_access_role"
        );

        $table = $schema->getTable("oro_account_role_to_website");
        $table->removeForeignKey($this->getConstraintName($table, "account_user_role_id"));
        $this->renameExtension->renameColumn(
            $schema,
            $queries,
            $table,
            "account_user_role_id",
            "customer_user_role_id"
        );
        $this->renameExtension->renameTable(
            $schema,
            $queries,
            "oro_account_role_to_website",
            "oro_customer_role_to_website"
        );

        $this->renameExtension->renameTable(
            $schema,
            $queries,
            "oro_account_user_role",
            "oro_customer_user_role"
        );
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     */
    private function renameCustomerSettings(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable("oro_account_user_settings");

        $table->dropIndex('unique_acc_user_website');
        $this->renameAccountUserId($schema, $queries, "oro_account_user_settings");

        $this->renameExtension->renameTable(
            $schema,
            $queries,
            'oro_account_user_settings',
            'oro_customer_user_settings'
        );

        /** @var ConfigManager $configManager */
        $configManager = $this->container->get('oro_entity_config.config_manager');
        $registry = $this->container->get('doctrine');
        $migration = new ConfigMigration($registry, $configManager);
        $migration->migrate(
            'Oro\Bundle\CustomerBundle\Entity\CustomerUserSettings',
            '.accountusersettings',
            '.customerusersettings'
        );
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     */
    private function renameAccountUserAddressToAddressType(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable("oro_acc_usr_adr_to_adr_type");

        $table->dropIndex('oro_account_user_adr_id_type_name_idx');

        $fk = $this->getConstraintName($table, 'account_user_address_id');
        $table->removeForeignKey($fk);
        $this->renameExtension->renameColumn(
            $schema,
            $queries,
            $table,
            "account_user_address_id",
            "customer_user_address_id"
        );

        $this->renameExtension->renameTable(
            $schema,
            $queries,
            "oro_acc_usr_adr_to_adr_type",
            "oro_cus_usr_adr_to_adr_type"
        );
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     */
    private function renameAccountAdrAdrTypeTable(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable("oro_account_adr_adr_type");

        $table->dropIndex('oro_account_adr_id_type_name_idx');

        $fk = $this->getConstraintName($table, 'account_address_id');
        $table->removeForeignKey($fk);
        $this->renameExtension->renameColumn(
            $schema,
            $queries,
            $table,
            "account_address_id",
            "customer_address_id"
        );

        $this->renameExtension->renameTable(
            $schema,
            $queries,
            "oro_account_adr_adr_type",
            "oro_customer_adr_adr_type"
        );
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     */
    private function renameAccountUserAddressTable(Schema $schema, QueryBag $queries)
    {
        $this->renameExtension->renameTable(
            $schema,
            $queries,
            "oro_account_user_address",
            "oro_customer_user_address"
        );
        /** @var ConfigManager $configManager */
        $configManager = $this->container->get('oro_entity_config.config_manager');
        $registry = $this->container->get('doctrine');
        $migration = new ConfigMigration($registry, $configManager);
        $migration->migrate(
            'Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress',
            '.accountuseraddress',
            '.customeruseraddress'
        );
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     */
    private function renameAccountAddressTable(Schema $schema, QueryBag $queries)
    {
        $this->renameExtension->renameTable(
            $schema,
            $queries,
            "oro_account_address",
            "oro_customer_address"
        );
        /** @var ConfigManager $configManager */
        $configManager = $this->container->get('oro_entity_config.config_manager');
        $registry = $this->container->get('doctrine');
        $migration = new ConfigMigration($registry, $configManager);
        $migration->migrate(
            'Oro\Bundle\CustomerBundle\Entity\CustomerAddress',
            '.accountaddress',
            '.customeraddress'
        );
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     */
    public function renameCustomerGroup(Schema $schema, QueryBag $queries)
    {
        $extension = $this->renameExtension;
        $schema->getTable('oro_account_group')->dropIndex('oro_account_group_name_idx');
        $extension->addIndex($schema, $queries, 'oro_account_group', ['name'], 'oro_customer_group_name_idx');
        $extension->renameTable(
            $schema,
            $queries,
            'oro_account_group',
            'oro_customer_group'
        );
        /** @var ConfigManager $configManager */
        $configManager = $this->container->get('oro_entity_config.config_manager');
        $registry = $this->container->get('doctrine');
        $migration = new ConfigMigration($registry, $configManager);
        $migration->migrate(
            'Oro\Bundle\CustomerBundle\Entity\CustomerGroup',
            '.accountgroup',
            '.customergroup'
        );
        $possibleNoteRelation = [
            'account_group_4a32a76a',
            'account_group_2aa0f32f',
            'account_group_8ca1514c',
            'account_group_87ec8cf4',
            'account_group_1125b02'
        ];
        foreach ($possibleNoteRelation as $relation) {
            if ($schema->getTable('oro_note')->hasColumn($relation.'_id')) {
                $schema->getTable('oro_note')->dropColumn($relation.'_id');
                $query = new UpdateNoteAssociationQuery($schema);
                $query->setFieldName($relation);
                $query->setTargetClass('CustomerGroup');
                $queries->addPostQuery($query);
            }
            $this->migrateConfig(
                $configManager,
                'Oro\Bundle\ActivityListBundle\Entity\ActivityList',
                'Oro\Bundle\CustomerBundle\Entity\CustomerGroup',
                $relation,
                'customer_group_58e0c3ec',
                RelationType::MANY_TO_MANY
            );
        }
        $tables = [
            'oro_rel_c3990ba665864788c78443',
            'oro_rel_c3990ba665864788b74044',
            'oro_rel_c3990ba6658647885abb70',
        ];
        foreach ($tables as $tableName) {
            if ($schema->hasTable($tableName)) {
                $table = $schema->getTable($tableName);
                $fk = $this->getConstraintName($table, 'accountgroup_id');
                $table->removeForeignKey($fk);
                $extension->renameColumn($schema, $queries, $table, 'accountgroup_id', 'customergroup_id');
                $extension->renameTable(
                    $schema,
                    $queries,
                    $tableName,
                    'oro_rel_c3990ba616cbf45899499b'
                );
            }
        }
        if ($schema->hasTable('oro_rel_6f8f552a65864788eebf8a')) {
            $table = $schema->getTable('oro_rel_6f8f552a65864788eebf8a');
            $fk = $this->getConstraintName($table, 'accountgroup_id');
            $table->removeForeignKey($fk);
            $extension->renameColumn($schema, $queries, $table, 'accountgroup_id', 'customergroup_id');
            $extension->renameTable(
                $schema,
                $queries,
                'oro_rel_6f8f552a65864788eebf8a',
                'oro_rel_6f8f552a16cbf458eebf8a'
            );
        }
        $migration = new ConfigMigration($registry, $configManager);
        $migration->migrate(
            'Oro\Bundle\CustomerBundle\Entity\CustomerGroup',
            '.accountgroup',
            '.customergroup'
        );
        $migration->migrate(
            'Oro\Bundle\NoteBundle\Entity\Note',
            '.accountgroup',
            '.customergroup'
        );
        $migration->migrate(
            'Oro\Bundle\ActivityListBundle\Entity\ActivityList',
            '.accountgroup',
            '.customergroup'
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @param Schema $schema
     * @param QueryBag $queries
     */
    private function renameCustomerUser(Schema $schema, QueryBag $queries)
    {
        $extension = $this->renameExtension;
        $configManager = $this->container->get('oro_entity_config.config_manager');
        $this->renameExtension->renameTable(
            $schema,
            $queries,
            'oro_account_user',
            'oro_customer_user'
        );

        $this->renameAccountUserId($schema, $queries, 'oro_audit');

        $this->renameAccountUserId($schema, $queries, 'oro_acc_pagestate');
        $this->renameExtension->renameTable(
            $schema,
            $queries,
            'oro_acc_pagestate',
            'oro_cus_pagestate'
        );

        $this->renameAccountUserId($schema, $queries, 'oro_account_user_sales_reps');
        $this->renameExtension->renameTable(
            $schema,
            $queries,
            'oro_account_user_sales_reps',
            'oro_customer_user_sales_reps'
        );

        $this->renameAccountUserId($schema, $queries, 'oro_acc_navigation_history');

        $accNavigationHistory = $schema->getTable('oro_acc_navigation_history');
        $accNavigationHistory->dropIndex('oro_acc_nav_history_route_idx');
        $accNavigationHistory->dropIndex('oro_acc_nav_history_entity_id_idx');
        $extension->addIndex(
            $schema,
            $queries,
            'oro_acc_navigation_history',
            ['route'],
            'oro_cus_nav_history_route_idx'
        );
        $extension->addIndex(
            $schema,
            $queries,
            'oro_acc_navigation_history',
            ['entity_id'],
            'oro_cus_nav_history_entity_id_idx'
        );

        $this->renameExtension->renameTable(
            $schema,
            $queries,
            'oro_acc_navigation_history',
            'oro_cus_navigation_history'
        );
        $this->renameAccountUserId($schema, $queries, 'oro_acc_navigation_item');
        $this->renameExtension->renameTable(
            $schema,
            $queries,
            'oro_acc_navigation_item',
            'oro_cus_navigation_item'
        );
        $this->renameAccountUserId($schema, $queries, 'oro_account_user_org');
        $this->renameExtension->renameTable(
            $schema,
            $queries,
            'oro_account_user_org',
            'oro_customer_user_org'
        );

        $queries->addQuery(new UpdateTableFieldQuery(
            'oro_email_template',
            'content',
            'oro_customer_frontend_account_user_confirmation',
            'oro_customer_frontend_customer_user_confirmation'
        ));

        $queries->addQuery(new UpdateTableFieldQuery(
            'oro_email_template',
            'content',
            'oro_customer_account_user_security_login',
            'oro_customer_customer_user_security_login'
        ));

        $queries->addQuery(new UpdateTableFieldQuery(
            'oro_email_template',
            'content',
            'oro_customer_frontend_account_user_password_reset',
            'oro_customer_frontend_customer_user_password_reset'
        ));
        $queries->addQuery(new UpdateTableFieldQuery(
            'oro_email_template',
            'entityName',
            'AccountUser',
            'CustomerUser'
        ));
        $queries->addQuery(new UpdateTableFieldQuery(
            'oro_email_template',
            'entityName',
            'AccountUser',
            'CustomerUser'
        ));
        $queries->addQuery(new UpdateTableFieldQuery(
            'oro_customer_user',
            'first_name',
            'AccountUser',
            'CustomerUser'
        ));
        $queries->addQuery(new UpdateTableFieldQuery(
            'oro_customer_user',
            'last_name',
            'AccountUser',
            'CustomerUser'
        ));
        $queries->addQuery(new UpdateTableFieldQuery(
            'oro_customer_user',
            'username',
            'account_user@example.com',
            'customer_user@example.com'
        ));
        $queries->addQuery(new UpdateTableFieldQuery(
            'oro_customer_user',
            'email',
            'account_user@example.com',
            'customer_user@example.com'
        ));
        $queries->addQuery(new UpdateTableFieldQuery(
            'oro_customer_user',
            'password',
            'sYCOBCtj8wbkvl6IFqAn43MR22NMOEqI8z368IYucept7U4w+MqGLIwvPTP/mpCfovQOKAl2GZBp0KAqqfB15A==',
            'ie6IPwSIHjZA7OWy5pTb9ae8dz94+ks5JOERDiDXyzhhneKnjsSJ8wKQXTmvVFUoGnLVY+yQheI89TgWjzHaOQ=='
        ));
        $queries->addQuery(new UpdateTableFieldQuery(
            'oro_migrations_data',
            'class_name',
            'LoadAccountUserRoles',
            'LoadCustomerUserRoles'
        ));
        $possibleNoteRelation = [
            'account_user_741cdecd',
            'account_user_1cc98a31',
            'account_user_7d31d338',
            'account_user_604160ea',
            'account_user_5feb43a7',
            'account_user_5919fc1d',
        ];
        foreach ($possibleNoteRelation as $relation) {
            if ($schema->getTable('oro_note')->hasColumn($relation.'_id')) {
                $table = $schema->getTable('oro_note');
                $fk = $this->getConstraintName($table, $relation.'_id');
                $table->removeForeignKey($fk);
                $table->dropColumn($relation.'_id');
                $query = new UpdateNoteAssociationQuery($schema);
                $query->setTargetClass('CustomerUser');
                $query->setFieldName($relation);
                $queries->addPostQuery($query);
            }
            $this->migrateConfig(
                $configManager,
                'Oro\Bundle\ActivityListBundle\Entity\ActivityList',
                'Oro\Bundle\CustomerBundle\Entity\CustomerUser',
                $relation,
                'customer_user_18bc0561',
                RelationType::MANY_TO_MANY
            );
        }
        if ($schema->hasTable('oro_rel_c3990ba6a6adb604bad737')) {
            //activitylist - customeruser
            $table = $schema->getTable('oro_rel_c3990ba6a6adb604bad737');
            $extension->renameColumn($schema, $queries, $table, 'accountuser_id', 'customeruser_id');
            $extension->renameTable(
                $schema,
                $queries,
                'oro_rel_c3990ba6a6adb604bad737',
                'oro_rel_c3990ba63708e583a2c61e'
            );
        }
        if ($schema->hasTable('oro_rel_6f8f552aa6adb604264ef1')) {
            $schema->dropTable('oro_rel_6f8f552aa6adb604264ef1');
        }
        $this->migrateConfig(
            $configManager,
            'Oro\Bundle\CalendarBundle\Entity\CalendarEvent',
            'Oro\Bundle\CustomerBundle\Entity\CustomerUser',
            'account_user_741cdecd',
            'customer_user_d5622eff',
            RelationType::MANY_TO_MANY
        );
        $this->migrateConfig(
            $configManager,
            'Oro\Bundle\NoteBundle\Entity\Note',
            'Oro\Bundle\CustomerBundle\Entity\CustomerUser',
            'account_user_741cdecd',
            'customer_user_d5622eff',
            RelationType::MANY_TO_MANY
        );
        $this->migrateConfig(
            $configManager,
            'Oro\Bundle\CalendarBundle\Entity\CalendarEvent',
            'Oro\Bundle\CustomerBundle\Entity\CustomerUser',
            'account_user_489123cf',
            'customer_user_d5622eff',
            RelationType::MANY_TO_MANY
        );
        $this->renameRelationTable(
            $schema,
            $queries,
            'oro_rel_46a29d19a6adb604264ef1',
            'oro_rel_46a29d193708e583c5ba51'
        );
        $this->renameRelationTable(
            $schema,
            $queries,
            'oro_rel_46a29d19a6adb604a9b8e1',
            'oro_rel_46a29d193708e583c5ba51'
        );
        $this->migrateConfig(
            $configManager,
            'Oro\Bundle\AttachmentBundle\Entity\Attachment',
            'Oro\Bundle\CustomerBundle\Entity\CustomerUser',
            'account_user_5919fc1d',
            'customer_user_539cf909',
            RelationType::MANY_TO_ONE
        );
        $this->migrateConfig(
            $configManager,
            'Oro\Bundle\AttachmentBundle\Entity\Attachment',
            'Oro\Bundle\CustomerBundle\Entity\CustomerUser',
            'account_user_1cc98a31',
            'customer_user_539cf909',
            RelationType::MANY_TO_ONE
        );
        $table = $schema->getTable('oro_attachment');

        $fk = $this->getConstraintName($table, 'account_user_5919fc1d_id');
        $table->removeForeignKey($fk);
        $extension->renameColumn($schema, $queries, $table, 'account_user_5919fc1d_id', 'customer_user_539cf909_id');

        /** @var ConfigManager $configManager */
        $configManager = $this->container->get('oro_entity_config.config_manager');
        $registry = $this->container->get('doctrine');
        $migration = new ConfigMigration($registry, $configManager);
        $migration->migrate(
            'Oro\Bundle\CustomerBundle\Entity\CustomerUser',
            '.accountuser',
            '.customeruser'
        );
        $migration->migrate(
            'Oro\Bundle\CustomerBundle\Entity\Customer',
            'account-accounts-select-grid',
            'customer-customers-select-grid'
        );
        $migration->migrate(
            'Oro\Bundle\CustomerBundle\Entity\Customer',
            'oro_customer_account_select',
            'oro_customer_customer_select'
        );
        $migration->migrate(
            'Oro\Bundle\ActivityListBundle\Entity\ActivityList',
            '.accountuser',
            '.customeruser'
        );
        $migration->migrate(
            'Oro\Bundle\AttachmentBundle\Entity\Attachment',
            '.accountuser',
            '.customeruser'
        );
        $migration->migrate(
            'Oro\Bundle\CalendarBundle\Entity\CalendarEvent',
            '.accountuser',
            '.customeruser'
        );
        $migration->migrate(
            'Oro\Bundle\EmailBundle\Entity\Email',
            '.accountuser',
            '.customeruser'
        );
        $migration->migrate(
            'Oro\Bundle\NoteBundle\Entity\Note',
            '.accountuser',
            '.customeruser'
        );
        $this->migrateConfig(
            $configManager,
            'Oro\Bundle\EmailBundle\Entity\Email',
            'Oro\Bundle\CustomerBundle\Entity\CustomerUser',
            'account_user_741cdecd',
            'customer_user_d5622eff',
            RelationType::MANY_TO_MANY
        );
        $this->migrateConfig(
            $configManager,
            'Oro\Bundle\EmailBundle\Entity\Email',
            'Oro\Bundle\CustomerBundle\Entity\CustomerUser',
            'account_user_489123cf',
            'customer_user_d5622eff',
            RelationType::MANY_TO_MANY
        );

        $this->renameRelationTable(
            $schema,
            $queries,
            'oro_rel_26535370a6adb604264ef1',
            'oro_rel_265353703708e583c5ba51'
        );
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     */
    private function renameAccWindowsStateTable(Schema $schema, QueryBag $queries)
    {
        $windowsState = $schema->getTable('oro_acc_windows_state');
        $windowsState->dropIndex('oro_acc_windows_state_acu_idx');
        $windowsStateForeignKey = $this->getConstraintName($windowsState, 'customer_user_id');
        $windowsState->removeForeignKey($windowsStateForeignKey);

        $this->renameExtension->renameTable(
            $schema,
            $queries,
            "oro_acc_windows_state",
            "oro_cus_windows_state"
        );
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     */
    private function renameAccNavItemPinbarTable(Schema $schema, QueryBag $queries)
    {
        $this->renameExtension->renameTable(
            $schema,
            $queries,
            "oro_acc_nav_item_pinbar",
            "oro_cus_nav_item_pinbar"
        );
    }

    /**
     * Sets the RenameExtension
     *
     * @param RenameExtension $renameExtension
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }

    /**
     * Get the order of this migration
     *
     * @return integer
     */
    public function getOrder()
    {
        return 1;
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     * @param string $tableName
     */
    private function renameAccountUserId(Schema $schema, QueryBag $queries, $tableName)
    {
        $table = $schema->getTable($tableName);
        $fk = $this->getConstraintName($table, 'account_user_id');
        $table->removeForeignKey($fk);
        foreach ($table->getIndexes() as $index) {
            if (!$index->isPrimary() && in_array('account_user_id', $index->getColumns())) {
                $table->dropIndex($index->getName());
            }
        }
        $this->renameExtension->renameColumn($schema, $queries, $table, 'account_user_id', 'customer_user_id');
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     * @param string $tableName
     */
    private function renameAccountId(Schema $schema, QueryBag $queries, $tableName)
    {
        $table = $schema->getTable($tableName);
        $fk = $this->getConstraintName($table, 'account_id');
        $table->removeForeignKey($fk);
        foreach ($table->getIndexes() as $index) {
            if (!$index->isPrimary() && in_array('account_id', $index->getColumns())) {
                $table->dropIndex($index->getName());
            }
        }
        $this->renameExtension->renameColumn($schema, $queries, $table, 'account_id', 'customer_id');
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     * @param string $tableName
     * @param string $newTableName
     */
    private function renameRelationTable(Schema $schema, QueryBag $queries, $tableName, $newTableName)
    {
        if ($schema->hasTable($tableName)) {
            $extension = $this->renameExtension;
            $table = $schema->getTable($tableName);
            $fk = $this->getConstraintName($table, 'accountuser_id');
            $table->removeForeignKey($fk);
            $extension->renameColumn($schema, $queries, $table, 'accountuser_id', 'customeruser_id');
            $extension->renameTable($schema, $queries, $tableName, $newTableName);
        }
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     */
    private function alterScopes(Schema $schema, QueryBag $queries)
    {
        /** @var ConfigManager $configManager */
        $configManager = $this->container->get('oro_entity_config.config_manager');

        $extension = $this->renameExtension;
        $table = $schema->getTable('oro_scope');
        if ($table->hasColumn('account_id')) {
            $fk = $this->getConstraintName($table, 'account_id');
            $table->removeForeignKey($fk);
            try {
                $fk = $this->getConstraintName($table, 'accountGroup_id');
                $table->removeForeignKey($fk);
                $extension->renameColumn($schema, $queries, $table, 'accountGroup_id', 'customerGroup_id');
            } catch (\LogicException $ex) {
                $fk = $this->getConstraintName($table, 'accountgroup_id');
                $table->removeForeignKey($fk);
                $extension->renameColumn($schema, $queries, $table, 'accountgroup_id', 'customergroup_id');
            }
            $extension->renameColumn($schema, $queries, $table, 'account_id', 'customer_id');
        }
        $this->migrateConfig(
            $configManager,
            'Oro\Bundle\ScopeBundle\Entity\Scope',
            'Oro\Bundle\CustomerBundle\Entity\Customer',
            'account',
            'customer',
            RelationType::MANY_TO_ONE
        );
        $this->migrateConfig(
            $configManager,
            'Oro\Bundle\ScopeBundle\Entity\Scope',
            'Oro\Bundle\CustomerBundle\Entity\CustomerGroup',
            'accountGroup',
            'customerGroup',
            RelationType::MANY_TO_ONE
        );
    }

    /**
     * @param QueryBag $queries
     */
    private function renameLoadedFixtures(QueryBag $queries)
    {
        $queries->addQuery(new UpdateTableFieldQuery(
            'oro_migrations_data',
            'class_name',
            'LoadAccountUserRoles',
            'LoadCustomerUserRoles'
        ));
        $queries->addQuery(new UpdateTableFieldQuery(
            'oro_migrations_data',
            'class_name',
            'Oro\Bundle\CustomerBundle\Migrations\Data\ORM\LoadAnonymousAccountGroup',
            'Oro\Bundle\CustomerBundle\Migrations\Data\ORM\LoadAnonymousCustomerGroup'
        ));
        $queries->addQuery(new UpdateTableFieldQuery(
            'oro_migrations_data',
            'class_name',
            'Oro\Bundle\CustomerBundle\Migrations\Data\Demo\ORM\LoadAccount',
            'Oro\Bundle\CustomerBundle\Migrations\Data\Demo\ORM\LoadCustomer'
        ));
        $queries->addQuery(new UpdateTableFieldQuery(
            'oro_migrations_data',
            'class_name',
            'Oro\Bundle\CustomerBundle\Migrations\Data\Demo\ORM\LoadAccount',
            'Oro\Bundle\CustomerBundle\Migrations\Data\Demo\ORM\LoadCustomer'
        ));
        $queries->addQuery(new UpdateTableFieldQuery(
            'oro_migrations_data',
            'class_name',
            'Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData',
            'Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData'
        ));
        $queries->addQuery(new UpdateTableFieldQuery(
            'oro_migrations_data',
            'class_name',
            'Oro\Bundle\MenuBundle\Migrations\Data\ORM\AddConditionForMyAccountMenu',
            'Oro\Bundle\MenuBundle\Migrations\Data\ORM\AddConditionForMyCustomerMenu'
        ));
        $queries->addQuery(new UpdateTableFieldQuery(
            'oro_migrations_data',
            'class_name',
            'Oro\Bundle\PaymentBundle\Migrations\Data\Demo\ORM\LoadPaymentTermToAccount',
            'Oro\Bundle\PaymentBundle\Migrations\Data\Demo\ORM\LoadPaymentTermToCustomer'
        ));
        $queries->addQuery(new UpdateTableFieldQuery(
            'oro_migrations_data',
            'class_name',
            'Oro\Bundle\PricingBundle\Migrations\Data\Demo\ORM\LoadPriceListToAccount',
            'Oro\Bundle\PricingBundle\Migrations\Data\Demo\ORM\LoadPriceListToCustomer'
        ));
        $queries->addQuery(new UpdateTableFieldQuery(
            'oro_config_value',
            'name',
            'anonymous_account_group',
            'anonymous_customer_group'
        ));

        $queries->addQuery(new UpdateTableFieldQuery(
            'oro_email_template',
            'name',
            'account_user_welcome_email',
            'customer_user_welcome_email'
        ));
        $queries->addQuery(new UpdateTableFieldQuery(
            'oro_email_template',
            'content',
            'accountUser',
            'customerUser'
        ));

        $queries->addQuery(new UpdateTableFieldQuery(
            'oro_email_template',
            'name',
            'account_user_confirmation_email',
            'customer_user_confirmation_email'
        ));

        $queries->addQuery(new UpdateTableFieldQuery(
            'oro_email_template',
            'name',
            'account_user_reset_password',
            'customer_user_reset_password'
        ));
    }

    private function updateEntityConfigAcl()
    {
        /** @var ConfigManager $configManager */
        $configManager = $this->container->get('oro_entity_config.config_manager');
        $registry = $this->container->get('doctrine');

        $migration = new ConfigMigration($registry, $configManager);

        $classes = [
            'Oro\Bundle\CustomerBundle\Entity\Customer',
            'Oro\Bundle\CustomerBundle\Entity\CustomerUser',
            'Oro\Bundle\CustomerBundle\Entity\CustomerAddress',
            'Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress',
            'Oro\Bundle\CustomerBundle\Entity\CustomerGroup',
            'Oro\Bundle\CustomerBundle\Entity\CustomerUserRole',
            'Oro\Bundle\CustomerBundle\Entity\CustomerUserSettings',
            'Oro\Bundle\ActivityListBundle\Entity\ActivityList',
            'Oro\Bundle\AttachmentBundle\Entity\Attachment',
            'Oro\Bundle\CalendarBundle\Entity\CalendarEvent',
            'Oro\Bundle\EmailBundle\Entity\Email',
            'Oro\Bundle\NoteBundle\Entity\Note',
            'Oro\Bundle\SaleBundle\Entity\QuoteProduct',
            'Oro\Bundle\ScopeBundle\Entity\Scope',
            'Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerProductVisibility',
            'Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerCategoryVisibility',
            'Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupCategoryVisibility',
            'Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupProductVisibility',
            'Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerProductVisibility',
            'Oro\Bundle\TaxBundle\Entity\CustomerTaxCode',
        ];
        foreach ($classes as $class) {
            $migration->migrate($class, '.account', '.customer');
        }
        $classes = [
            'Oro\Bundle\CheckoutBundle\Entity\Checkout',
            'Oro\Bundle\InvoiceBundle\Entity\Invoice',
            'Oro\Bundle\OrderBundle\Entity\Order',
            'Oro\Bundle\RFPBundle\Entity\Request',
            'Oro\Bundle\SaleBundle\Entity\Quote',
            'Oro\Bundle\ShoppingListBundle\Entity\ShoppingList',
            'Oro\Bundle\ShoppingListBundle\Entity\LineItem',
        ];
        foreach ($classes as $class) {
            $migration->migrate($class, 'account_user_id', 'customer_user_id');
            $migration->migrate($class, 'accountUser', 'customerUser');
        }
        $migration->migrate('Oro\Bundle\CustomerBundle\Entity\CustomerUser', 'account', 'customer');
        $migration->migrate('Oro\Bundle\CustomerBundle\Entity\CustomerUserRole', 'account', 'customer');

        $migration->migrate(
            'Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerProductVisibility',
            '.account',
            '.customer'
        );
    }
}
