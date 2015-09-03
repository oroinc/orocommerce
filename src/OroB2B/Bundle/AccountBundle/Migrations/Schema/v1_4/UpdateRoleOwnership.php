<?php

namespace OroB2B\Bundle\AccountBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use OroB2B\Bundle\AccountBundle\Migrations\Schema\OroB2BAccountBundleInstaller;

class UpdateRoleOwnership implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable(OroB2BAccountBundleInstaller::ORO_B2B_ACCOUNT_USER_ROLE_TABLE_NAME);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_id', 'integer', ['notnull' => false]);
        $table->addUniqueIndex(['account_id', 'label'], 'orob2b_acc_role_idx');
        $table = $schema->getTable(OroB2BAccountBundleInstaller::ORO_B2B_ACCOUNT_USER_ROLE_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(OroB2BAccountBundleInstaller::ORO_ORGANIZATION_TABLE_NAME),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(OroB2BAccountBundleInstaller::ORO_B2B_ACCOUNT_TABLE_NAME),
            ['account_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
