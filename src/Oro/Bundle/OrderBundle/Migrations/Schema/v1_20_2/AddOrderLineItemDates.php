<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v1_20_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddOrderLineItemDates implements Migration, OrderedMigrationInterface
{
    #[\Override]
    public function getOrder(): int
    {
        return 10;
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_order_line_item');
        if (!$table->hasColumn('created_at')) {
            $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)', 'notnull' => false]);
        }
        if (!$table->hasColumn('updated_at')) {
            $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)', 'notnull' => false]);
        }
    }
}
