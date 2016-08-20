<?php

namespace Oro\Bundle\SaleBundle\Migrations\Schema\v1_5;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroSaleBundlePriceListColumn implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('orob2b_sale_quote');
        $table->removeForeignKey('FK_4F66B6F65688DED7');
        $table->dropColumn('price_list_id');
    }
}
