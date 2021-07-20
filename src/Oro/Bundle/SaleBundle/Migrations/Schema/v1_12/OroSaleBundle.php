<?php

namespace Oro\Bundle\SaleBundle\Migrations\Schema\v1_12;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\CustomerBundle\Migrations\Schema\v1_10\ConfigMigration;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\MigrationConstraintTrait;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class OroSaleBundle implements
    Migration,
    RenameExtensionAwareInterface,
    OrderedMigrationInterface,
    ContainerAwareInterface
{
    use MigrationConstraintTrait;
    use ContainerAwareTrait;

    /**
     * @var RenameExtension
     */
    private $renameExtension;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->updateOroQuoteAddressTable($schema, $queries);
        $this->updateAccountRelations($schema, $queries);
    }

    private function updateOroQuoteAddressTable(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_quote_address');

        $fkAccountAddress = $this->getConstraintName($table, 'account_address_id');
        $table->removeForeignKey($fkAccountAddress);

        $fkAccountUserAddress = $this->getConstraintName($table, 'account_user_address_id');
        $table->removeForeignKey($fkAccountUserAddress);

        $this->renameExtension->renameColumn(
            $schema,
            $queries,
            $table,
            'account_address_id',
            'customer_address_id'
        );

        $this->renameExtension->renameColumn(
            $schema,
            $queries,
            $table,
            'account_user_address_id',
            'customer_user_address_id'
        );
    }

    private function updateAccountRelations(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_sale_quote');
        $table->removeForeignKey($this->getConstraintName($table, 'account_user_id'));
        $this->renameExtension->renameColumn(
            $schema,
            $queries,
            $table,
            'account_user_id',
            'customer_user_id'
        );
        $table->removeForeignKey($this->getConstraintName($table, 'account_id'));
        $this->renameExtension->renameColumn(
            $schema,
            $queries,
            $table,
            'account_id',
            'customer_id'
        );

        $table = $schema->getTable('oro_quote_demand');
        $table->removeForeignKey($this->getConstraintName($table, 'account_user_id'));
        $this->renameExtension->renameColumn(
            $schema,
            $queries,
            $table,
            'account_user_id',
            'customer_user_id'
        );
        $table->removeForeignKey($this->getConstraintName($table, 'account_id'));
        $this->renameExtension->renameColumn(
            $schema,
            $queries,
            $table,
            'account_id',
            'customer_id'
        );

        $table = $schema->getTable('oro_quote_assigned_acc_users');
        $table->removeForeignKey($this->getConstraintName($table, 'account_user_id'));
        $this->renameExtension->renameColumn(
            $schema,
            $queries,
            $table,
            'account_user_id',
            'customer_user_id'
        );
        $this->renameExtension->renameTable(
            $schema,
            $queries,
            'oro_quote_assigned_acc_users',
            'oro_quote_assigned_cus_users'
        );

        $table = $schema->getTable('oro_sale_quote_product');
        $this->renameExtension->renameColumn(
            $schema,
            $queries,
            $table,
            'comment_account',
            'comment_customer'
        );
        /** @var ConfigManager $configManager */
        $configManager = $this->container->get('oro_entity_config.config_manager');
        $registry = $this->container->get('doctrine');

        $migration = new ConfigMigration($registry, $configManager);
        $migration->migrate('Oro\Bundle\SaleBundle\Entity\QuoteDemand', 'accountUser', 'customerUser');
    }

    /**
     * Sets the RenameExtension
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
