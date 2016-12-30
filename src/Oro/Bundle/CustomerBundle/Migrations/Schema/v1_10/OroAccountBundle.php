<?php

namespace Oro\Bundle\CustomerBundle\Migrations\Schema\v1_10;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
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
