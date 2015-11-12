<?php

namespace OroB2B\Bundle\PricingBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\SqlMigrationQuery;

class AddPriorityToPriceListRelationTables implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addPriorityToOroB2BPriceListToAccount($schema, $queries);
        $this->addPriorityToOroB2BPriceListToAccountGroup($schema, $queries);
        $this->addPriorityToOroB2BPriceListToWebsite($schema, $queries);
    }

    /**
     * @param Schema $schema
     */
    protected function addPriorityToOroB2BPriceListToAccount(Schema $schema, QueryBag $queries)
    {
        $this->addPriorityToTable($schema, $queries, 'orob2b_price_list_to_account');
    }

    /**
     * @param Schema $schema
     */
    protected function addPriorityToOroB2BPriceListToAccountGroup(Schema $schema, QueryBag $queries)
    {
        $this->addPriorityToTable($schema, $queries, 'orob2b_price_list_to_c_group');
    }

    /**
     * @param Schema $schema
     */
    protected function addPriorityToOroB2BPriceListToWebsite(Schema $schema, QueryBag $queries)
    {
        $this->addPriorityToTable($schema, $queries, 'orob2b_price_list_to_website');
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     * @param string $tableName
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function addPriorityToTable(Schema $schema, QueryBag $queries, $tableName)
    {
        $table = $schema->getTable($tableName);
        $table->addColumn('priority', 'integer');
        $queries->addPostQuery(new SqlMigrationQuery("UPDATE $tableName SET priority = 100;"));
    }
}
