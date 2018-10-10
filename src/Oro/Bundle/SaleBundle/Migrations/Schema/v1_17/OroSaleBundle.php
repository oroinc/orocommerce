<?php

namespace Oro\Bundle\SaleBundle\Migrations\Schema\v1_17;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Adds guest access id field with data
 */
class OroSaleBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_sale_quote');

        if (!$table->hasColumn('guest_access_id')) {
            $table->addColumn('guest_access_id', 'guid', ['notnull' => false]);
        }
    }
}
