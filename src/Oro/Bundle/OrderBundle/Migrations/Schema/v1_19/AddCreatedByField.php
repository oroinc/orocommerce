<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v1_19;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddCreatedByField implements Migration
{
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_order');
        if (!$table->hasColumn('created_by_user_id')) {
            $table->addColumn('created_by_user_id', 'integer', ['notnull' => false]);

            $table->addForeignKeyConstraint(
                $schema->getTable('oro_user'),
                ['created_by_user_id'],
                ['id'],
                ['onUpdate' => null, 'onDelete' => 'SET NULL']
            );
        }
    }
}
