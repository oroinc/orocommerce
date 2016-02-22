<?php

namespace OroB2B\Bundle\PricingBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddMergeAllowedColumn implements Migration
{

    /**
     * Modifies the given schema to apply necessary changes of a database
     * The given query bag can be used to apply additional SQL queries before and after schema changes
     *
     * @param Schema $schema
     * @param QueryBag $queries
     * @return void
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $schema->getTable('orob2b_price_list_to_acc_group')
            ->addColumn('merge_allowed', 'boolean');

        $schema->createTable('orob2b_price_list_to_account')
            ->addColumn('merge_allowed', 'boolean');

        $schema->createTable('orob2b_price_list_to_website')
            ->addColumn('merge_allowed', 'boolean');
    }
}
