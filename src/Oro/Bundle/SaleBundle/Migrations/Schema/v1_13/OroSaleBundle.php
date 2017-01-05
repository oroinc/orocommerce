<?php

namespace Oro\Bundle\SaleBundle\Migrations\Schema\v1_13;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroSaleBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_sale_quote');
        $table->addColumn('shipping_method_locked', 'boolean');
        $table->addColumn('allow_unlisted_shipping_method', 'boolean');
    }
}
