<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v1_15;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Adds shipping_method, shipping_method_type, estimated_shipping_cost_amount to OrderLineItem
 */
class AddShippingColumnsToOrderLineItem implements Migration
{
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_order_line_item');

        if (!$table->hasColumn('shipping_method')) {
            $table->addColumn('shipping_method', 'string', ['notnull' => false, 'length' => 255]);
        }

        if (!$table->hasColumn('shipping_method_type')) {
            $table->addColumn('shipping_method_type', 'string', ['notnull' => false, 'length' => 255]);
        }

        if (!$table->hasColumn('shipping_estimate_amount')) {
            $table->addColumn('shipping_estimate_amount', 'money', [
                'notnull' => false,
                'precision' => 19,
                'scale' => 4,
                'comment' => '(DC2Type:money)'
            ]);
        }
    }
}
