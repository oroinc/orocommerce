<?php

namespace Oro\Bundle\PricingBundle\Migrations\Schema\v1_22;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Drop `oro_combined_price_unq_idx' and create same `oro_combined_price_idx`
 */
class ChangeCombinedPriceIndexes implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table  = $schema->getTable('oro_price_product_combined');
        if ($table->hasIndex('oro_combined_price_unq_idx')) {
            $table->dropIndex('oro_combined_price_unq_idx');
        }

        if (!$table->hasIndex('oro_combined_price_idx')) {
            $table->addIndex(
                [
                    'combined_price_list_id',
                    'product_id',
                    'currency',
                    'unit_code',
                    'quantity'
                ],
                'oro_combined_price_idx'
            );
        }
    }
}
