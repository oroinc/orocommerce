<?php

namespace Oro\Bundle\CustomerBundle\Migrations\Schema\v1_10;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\MigrationConstraintTrait;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroAccountBundle implements Migration, RenameExtensionAwareInterface
{
    use MigrationConstraintTrait;

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
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     */
    private function renameCustomerUserSidebarWidget(Schema $schema, QueryBag $queries)
    {

        $table = $schema->getTable("oro_account_user_sdbar_wdg");

        $table->dropIndex("oro_cus_sdbr_wdgs_usr_place_idx");
        $table->dropIndex("oro_cus_sdar_wdgs_pos_idx");

        $fk = $this->getConstraintName($table, 'account_user_id');
        $table->removeForeignKey($fk);
        $table->renameColumn("account_user_id", "customer_user_id");
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_account_user'),
            ['customer_user_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );

        $table->addIndex(['position'], 'oro_cus_sdar_wdgs_pos_idx', []);
        $table->addIndex(['customer_user_id', 'placement'], 'oro_cus_sdbr_wdgs_usr_place_idx', []);

        $this->renameExtension->renameTable(
            $schema,
            $queries,
            "oro_account_user_sdbar_wdg",
            "oro_customer_user_sdbar_wdg"
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
}
