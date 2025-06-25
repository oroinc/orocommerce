<?php

namespace Oro\Bundle\SaleBundle\Migrations\Schema\v1_20_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddVisitorToQuote implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_sale_quote');
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
