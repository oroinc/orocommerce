<?php

namespace Oro\Bundle\CustomerBundle\Migrations\Schema\v1_10;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\FrontendBundle\Migration\UpdateExtendRelationTrait;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\MigrationConstraintTrait;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

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
        $this->renameCustomerUserSidebarWidget($schema, $queries);
        $this->renameAccountUserSidebarState($schema, $queries);
        $this->renameCustomerSettings($schema, $queries);
        $this->renameAccountUserAddressToAddressType($schema, $queries);
        $this->renameAccountAdrAdrTypeTable($schema, $queries);
        $this->renameAccountUserAddressTable($schema, $queries);
        $this->renameAccountAddressTable($schema, $queries);
        $this->renameCustomerGroup($schema, $queries);
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

        $fk = $this->getConstraintName($table, 'account_user_id');
        $table->removeForeignKey($fk);
        $this->renameExtension->renameColumn($schema, $queries, $table, "account_user_id", "customer_user_id");

        $this->renameExtension->renameTable(
            $schema,
            $queries,
            "oro_account_user_sdbar_st",
            "oro_customer_user_sdbar_st"
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

        $fk = $this->getConstraintName($table, 'account_user_id');
        $table->removeForeignKey($fk);
        $this->renameExtension->renameColumn($schema, $queries, $table, 'account_user_id', 'customer_user_id');

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
            'Oro\Bundle\CustomerBundle\Entity\CustomerUserSettings',
            '.accountgroup',
            '.customergroup'
        );
        $possibleNoteRelation = [
            'account_group_4a32a76a',
            'account_group_2aa0f32f',
            'account_group_8ca1514c',
            'account_group_87ec8cf4',
        ];
        foreach ($possibleNoteRelation as $relation) {
            if ($schema->getTable('oro_note')->hasColumn($relation.'_id')) {
                $schema->getTable('oro_note')->dropColumn($relation.'_id');
                $query = new UpdateNoteAssociationQuery($schema);
                $query->setFieldName($relation);
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
}
