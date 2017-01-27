<?php

namespace Oro\Bundle\PricingBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroPricingBundle implements Migration
{
    /**
     * @inheritDoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_price_product_combined');

        $table->dropIndex('oro_combined_price_uidx');

        $table->addUniqueIndex(
            [ 'product_id',
              'combined_price_list_id',
              'quantity',
              'unit_code',
              'currency'],
            'oro_combined_price_uidx'
        );
        $table->addIndex(
            ['combined_price_list_id',
             'product_id',
             'unit_code',
             'quantity',
             'currency'],
            'oro_combined_price_idx'
        );
        $table->addIndex(
            ['combined_price_list_id', 'product_id', 'merge_allowed'],
            'oro_cmb_price_mrg_idx'
        );
    }
}
