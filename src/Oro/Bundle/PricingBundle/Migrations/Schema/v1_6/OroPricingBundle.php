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
        $this->dropTriggersTable($schema);
        $this->updatePriceListTable($schema);
    }

    /**
     * @param Schema $schema
     */
    protected function dropTriggersTable(Schema $schema)
    {
        $schema->dropTable('orob2b_prod_price_ch_trigger');
        $schema->dropTable('orob2b_price_list_ch_trigger');
    }

    /**
     * @param Schema $schema
     */
    protected function updatePriceListTable(Schema $schema)
    {
        $table = $schema->getTable('orob2b_price_list');
        $table->addColumn('actual', 'boolean', ['notnull' => true , 'default' => true]);
    }
}
