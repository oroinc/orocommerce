<?php

namespace Oro\Bundle\RFPBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\MigrationConstraintTrait;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroRFPBundle implements Migration, RenameExtensionAwareInterface, OrderedMigrationInterface
{
    use RenameExtensionAwareTrait;
    use MigrationConstraintTrait;

    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_rfp_request');
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

        $table = $schema->getTable('oro_rfp_assigned_acc_users');
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
            'oro_rfp_assigned_acc_users',
            'oro_rfp_assigned_cus_users'
        );
    }

    #[\Override]
    public function getOrder()
    {
        return 1;
    }
}
