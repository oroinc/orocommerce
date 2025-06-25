<?php

namespace Oro\Bundle\RFPBundle\Migrations\Schema\v1_15_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Adds guest access id field.
 */
class AddVisitorToRequest implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_rfp_request');
        if (!$table->hasColumn('visitor_id')) {
            $table->addColumn('visitor_id', 'integer', ['notnull' => false]);
            $table->addForeignKeyConstraint(
                $schema->getTable('oro_customer_visitor'),
                ['visitor_id'],
                ['id'],
                ['onDelete' => 'SET NULL', 'onUpdate' => null]
            );
        }
    }
}
