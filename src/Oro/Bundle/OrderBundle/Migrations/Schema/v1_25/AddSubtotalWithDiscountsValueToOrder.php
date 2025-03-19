<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v1_25;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddSubtotalWithDiscountsValueToOrder implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_order');
        if (!$table->hasColumn('subtotal_with_discounts_value')) {
            $table->addColumn(
                'subtotal_with_discounts_value',
                'money_value',
                ['notnull' => false, 'precision' => 0, 'comment' => '(DC2Type:money_value)']
            );
        }
        if (!$table->hasColumn('subtotal_with_discounts_currency')) {
            $table->addColumn(
                'subtotal_with_discounts_currency',
                'currency',
                ['length' => 3, 'notnull' => false, 'comment' => '(DC2Type:currency)']
            );
        }
    }
}
