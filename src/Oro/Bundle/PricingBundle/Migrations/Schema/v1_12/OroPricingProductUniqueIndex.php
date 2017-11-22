<?php

namespace Oro\Bundle\PricingBundle\Migrations\Schema\v1_12;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroPricingProductUniqueIndex implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->changeUniqIndex($schema);
    }

    /**
     * Update Extended Entity
     */
    private function changeUniqIndex(Schema $schema)
    {
        $table = $schema->getTable('oro_price_product_combined');
        $table->dropIndex('oro_combined_price_uidx');
        $table->addUniqueIndex(
            [
                'combined_price_list_id',
                'currency',
                'product_id',
                'quantity',
                'unit_code'
            ],
            'oro_combined_price_uidx'
        );
    }
}
