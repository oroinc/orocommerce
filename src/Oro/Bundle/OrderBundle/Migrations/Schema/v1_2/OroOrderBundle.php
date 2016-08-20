<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v1_2;

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
        $this->alterAddressTable($schema);
        $this->alterOrderTable($schema);
    }

    /**
     * {@inheritdoc}
     */
    public function alterOrderTable(Schema $schema)
    {
        $table = $schema->getTable('orob2b_order');
        $table->addColumn('shipping_cost_amount', 'money', [
            'notnull' => false,
            'precision' => 19,
            'scale' => 4,
            'comment' => '(DC2Type:money)'
        ]);
        $table->addColumn(
            'total',
            'money',
            ['notnull' => false, 'precision' => 19, 'scale' => 4, 'comment' => '(DC2Type:money)']
        );
        $table->addColumn('source_entity_class', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('source_entity_id', 'integer', ['notnull' => false]);
        $table->addColumn('source_entity_identifier', 'string', ['notnull' => false, 'length' => 255]);
    }

    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function alterAddressTable(Schema $schema)
    {
        $table = $schema->getTable('orob2b_order_address');
        $table->addColumn('from_external_source', 'boolean', ['notnull' => true, 'default' => false]);
    }
}
