<?php

namespace OroB2B\Bundle\AccountBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class UpdateAddressOwnership implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('orob2b_account_address');
        $table->addColumn('system_org_id', 'integer', ['notnull' => false]);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['system_org_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );

        $table = $schema->getTable('orob2b_account_user_address');
        $table->addColumn('system_org_id', 'integer', ['notnull' => false]);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['system_org_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }
}
