<?php

namespace OroB2B\Bundle\ShippingBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BShippingBundle implements Migration
{
    const ORO_B2B_SHIPPING_RULE_TABLE_NAME = 'orob2b_shipping_rule';
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOrob2BShippingRuleTable($schema);
    }

    /**
     * Create orob2b_shipping_rule table
     *
     * @param Schema $schema
     */
    protected function createOrob2BShippingRuleTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_B2B_SHIPPING_RULE_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['notnull' => true, 'length' => 255]);
        $table->addColumn('status', 'string', ['length' => 16]);
        $table->addColumn('sort_order', 'integer', ['notnull' => true]);
        $table->addColumn('conditions', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['name', 'sort_order']);
    }
}
