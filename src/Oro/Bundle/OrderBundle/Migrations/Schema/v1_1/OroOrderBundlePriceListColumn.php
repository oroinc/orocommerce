<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroOrderBundlePriceListColumn implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('orob2b_order');
        $table->removeForeignKey('fk_orob2b_order_price_list_id');
        $table->dropColumn('price_list_id');
    }
}
