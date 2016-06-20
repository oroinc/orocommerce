<?php

namespace OroB2B\Bundle\SaleBundle\Migrations\Schema\v1_9;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class EnableQuoteExpireProcess implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        if (!$schema->hasTable('oro_process_definition')) {
            return;
        }

        $queries->addQuery(new ParametrizedSqlMigrationQuery(
            'UPDATE oro_process_definition SET enabled = TRUE WHERE name = :name',
            ['name' => 'expire_quotes']
        ));
    }
}
