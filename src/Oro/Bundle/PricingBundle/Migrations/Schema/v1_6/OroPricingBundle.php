<?php

namespace Oro\Bundle\PricingBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\MigrationConstraintTrait;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroPricingBundle implements
    Migration,
    RenameExtensionAwareInterface,
    OrderedMigrationInterface
{
    use MigrationConstraintTrait;

    /**
     * @var RenameExtension
     */
    private $renameExtension;

    /**
     * @inheritDoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->renameCustomerColumns($schema, $queries);
        $this->renameCustomerTables($schema, $queries);
        $schema->dropTable('oro_price_product_minimal');
    }

    private function renameCustomerTables(Schema $schema, QueryBag $queries)
    {
        $extension = $this->renameExtension;

        $extension->renameTable($schema, $queries, 'oro_price_list_to_acc_group', 'oro_price_list_to_cus_group');
        $extension->renameTable($schema, $queries, 'oro_price_list_acc_gr_fb', 'oro_price_list_cus_gr_fb');
        $extension->renameTable($schema, $queries, 'oro_cmb_plist_to_acc_gr', 'oro_cmb_plist_to_cus_gr');

        $extension->renameTable($schema, $queries, 'oro_price_list_to_account', 'oro_price_list_to_customer');
        $extension->renameTable($schema, $queries, 'oro_price_list_acc_fb', 'oro_price_list_cus_fb');
        $extension->renameTable($schema, $queries, 'oro_cmb_price_list_to_acc', 'oro_cmb_price_list_to_cus');
    }

    private function renameCustomerColumns(Schema $schema, QueryBag $queries)
    {
        $extension = $this->renameExtension;

        $table = $schema->getTable('oro_price_list_to_acc_group');
        $fk = $this->getConstraintName($table, 'account_group_id');
        $table->removeForeignKey($fk);
        $extension->renameColumn($schema, $queries, $table, 'account_group_id', 'customer_group_id');

        $table = $schema->getTable('oro_price_list_acc_gr_fb');
        $fk = $this->getConstraintName($table, 'account_group_id');
        $table->removeForeignKey($fk);
        $table->dropIndex('oro_price_list_acc_gr_fb_unq');
        $extension->renameColumn($schema, $queries, $table, 'account_group_id', 'customer_group_id');

        $table = $schema->getTable('oro_cmb_plist_to_acc_gr');
        $fk = $this->getConstraintName($table, 'account_group_id');
        $table->removeForeignKey($fk);
        $table->dropIndex('oro_cpl_to_acc_gr_ws_unq');
        $extension->renameColumn($schema, $queries, $table, 'account_group_id', 'customer_group_id');

        $table = $schema->getTable('oro_price_list_to_account');
        $fk = $this->getConstraintName($table, 'account_id');
        $table->removeForeignKey($fk);
        $extension->renameColumn($schema, $queries, $table, 'account_id', 'customer_id');

        $table = $schema->getTable('oro_price_list_acc_fb');
        $fk = $this->getConstraintName($table, 'account_id');
        $table->removeForeignKey($fk);
        $table->dropIndex('oro_price_list_acc_fb_unq');
        $extension->renameColumn($schema, $queries, $table, 'account_id', 'customer_id');

        $table = $schema->getTable('oro_cmb_price_list_to_acc');
        $fk = $this->getConstraintName($table, 'account_id');
        $table->removeForeignKey($fk);
        $table->dropIndex('oro_cpl_to_acc_ws_unq');
        $extension->renameColumn($schema, $queries, $table, 'account_id', 'customer_id');
    }

    /**
     * {@inheritdoc}
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
