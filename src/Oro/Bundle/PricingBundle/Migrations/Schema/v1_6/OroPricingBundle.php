<?php

namespace Oro\Bundle\PricingBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroPricingBundle implements Migration
{

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('orob2b_price_rule');
        $table->addColumn('quantity_expression', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('currency_expression', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('product_unit_expression', 'string', ['notnull' => false, 'length' => 255]);
    }
}
