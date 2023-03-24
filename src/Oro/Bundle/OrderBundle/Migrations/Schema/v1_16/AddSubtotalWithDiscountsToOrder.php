<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v1_16;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddSubtotalWithDiscountsToOrder implements Migration
{
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_order');
        if (!$table->hasColumn('subtotal_with_discounts')) {
            $table->addColumn(
                'subtotal_with_discounts',
                'money',
                ['notnull' => false, 'precision' => 19, 'scale' => 4, 'comment' => '(DC2Type:money)']
            );
        }
    }
}
