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
        $table->addColumn('quantity_expression', 'text', ['notnull' => false]);
        $table->addColumn('currency_expression', 'text', ['notnull' => false]);
        $table->addColumn('product_unit_expression', 'text', ['notnull' => false]);
    }
}
