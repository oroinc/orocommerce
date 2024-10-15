<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v1_20_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\SqlMigrationQuery;

class FillDefaultOrderLineItemDates implements Migration, OrderedMigrationInterface
{
    #[\Override]
    public function getOrder(): int
    {
        return 20;
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_order_line_item');
        if ($table->hasColumn('created_at')
            && $table->hasColumn('updated_at')
            && !$table->getColumn('created_at')->getNotnull()
            && !$table->getColumn('updated_at')->getNotnull()
        ) {
            $queries->addQuery(new SqlMigrationQuery(
                'UPDATE oro_order_line_item'
                . " SET created_at = TIMEZONE('UTC', NOW()), updated_at = TIMEZONE('UTC', NOW())"
                . ' WHERE order_id IS NULL'
            ));
            $queries->addQuery(new SqlMigrationQuery(
                'UPDATE oro_order_line_item'
                . ' SET created_at = oro_order.created_at, updated_at = oro_order.updated_at'
                . ' FROM oro_order WHERE oro_order.id = order_id AND order_id IS NOT NULL'
            ));
        }
    }
}
