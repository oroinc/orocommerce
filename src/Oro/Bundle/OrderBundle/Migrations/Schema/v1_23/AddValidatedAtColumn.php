<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v1_23;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Add validated_at column to oro_order_address table.
 */
class AddValidatedAtColumn implements Migration
{
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_order_address');

        if (!$table->hasColumn('validated_at')) {
            $table->addColumn('validated_at', 'datetime', ['notnull' => false]);
        }
    }
}
