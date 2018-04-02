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

        $table->addIndex(
            ['combined_price_list_id', 'product_id', 'merge_allowed'],
            'oro_cmb_price_mrg_idx'
        );
    }
}
