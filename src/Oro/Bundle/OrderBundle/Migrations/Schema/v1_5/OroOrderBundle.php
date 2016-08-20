<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v1_5;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroOrderBundle implements Migration
{

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addShippingMethodColumns($schema);
    }

    /**
     * Add shipping_method, shipping_method_type columns
     *
     * @param Schema $schema
     */
    protected function addShippingMethodColumns(Schema $schema)
    {
        $table = $schema->getTable('orob2b_order');
        $table->addColumn('shipping_method', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('shipping_method_type', 'string', ['notnull' => false, 'length' => 255]);
    }
}
