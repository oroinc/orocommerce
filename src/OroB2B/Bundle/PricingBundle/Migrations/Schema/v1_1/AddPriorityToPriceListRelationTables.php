<?php

namespace OroB2B\Bundle\PricingBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddPriorityToPriceListRelationTables implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addPriorityToOroB2BPriceListToAccount($schema);
        $this->addPriorityToOroB2BPriceListToAccountGroup($schema);
        $this->addPriorityToOroB2BPriceListToWebsite($schema);
    }

    /**
     * @param Schema $schema
     */
    protected function addPriorityToOroB2BPriceListToAccount(Schema $schema)
    {
        $this->addPriorityToTable($schema, 'orob2b_price_list_to_account');
    }

    /**
     * @param Schema $schema
     */
    protected function addPriorityToOroB2BPriceListToAccountGroup(Schema $schema)
    {
        $this->addPriorityToTable($schema, 'orob2b_price_list_to_c_group');
    }

    /**
     * @param Schema $schema
     */
    protected function addPriorityToOroB2BPriceListToWebsite(Schema $schema)
    {
        $this->addPriorityToTable($schema, 'orob2b_price_list_to_website');
    }

    /**
     * @param Schema $schema
     * @param string $tableName
     */
    protected function addPriorityToTable(Schema $schema, $tableName)
    {
        $table = $schema->getTable($tableName);
        $table->addColumn('priority', 'integer');
    }
}
