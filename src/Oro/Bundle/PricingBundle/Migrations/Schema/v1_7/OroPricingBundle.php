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
            ['combined_price_list_id', 'product_id', 'unit_code', 'quantity'],
            'oro_combined_price_2_uidx'
        );
        $table->addIndex(
            ['combined_price_list_id', 'product_id', 'merge_allowed'],
            'oro_combined_price_3_uidx'
        );
    }
}
