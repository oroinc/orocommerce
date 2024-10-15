<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v1_20_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\SqlMigrationQuery;

class MakeOrderLineItemDatesNotNull implements Migration, OrderedMigrationInterface
{
    #[\Override]
    public function getOrder(): int
    {
        return 30;
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_order_line_item');
        if ($table->hasColumn('created_at') && !$table->getColumn('created_at')->getNotnull()) {
            $table->getColumn('created_at')->setNotnull(true);
            $queries->addPostQuery(new SqlMigrationQuery(
                'ALTER TABLE oro_order_line_item ALTER COLUMN created_at DROP DEFAULT'
            ));
        }
        if ($table->hasColumn('updated_at') && !$table->getColumn('updated_at')->getNotnull()) {
            $table->getColumn('updated_at')->setNotnull(true);
            $queries->addPostQuery(new SqlMigrationQuery(
                'ALTER TABLE oro_order_line_item ALTER COLUMN updated_at DROP DEFAULT'
            ));
        }
    }
}
