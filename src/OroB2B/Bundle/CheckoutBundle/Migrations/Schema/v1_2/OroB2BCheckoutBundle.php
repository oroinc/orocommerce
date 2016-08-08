<?php

namespace OroB2B\Bundle\CheckoutBundle\Migrations\Schema\v2_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BCheckoutBundle implements Migration
{

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addCheckoutTypeColumn($schema);
    }

    /**
     * Add shipping_method, shipping_method_type columns
     *
     * @param Schema $schema
     */
    protected function addCheckoutTypeColumn(Schema $schema)
    {
        $table = $schema->getTable('orob2b_checkout');
        $table->addColumn('shipping_method', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('shipping_method_type', 'string', ['notnull' => false, 'length' => 255]);
    }
}
