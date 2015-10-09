<?php

namespace OroB2B\Bundle\AccountBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BAccountBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $schema->dropTable('orob2b_audit_field');
        $schema->dropTable('orob2b_audit');

        $schema->getTable('oro_audit')
            ->addForeignKeyConstraint(
                $schema->getTable('orob2b_account_user'),
                ['account_user_id'],
                ['id'],
                ['onDelete' => 'CASCADE', 'onUpdate' => null]
            );
    }
}
