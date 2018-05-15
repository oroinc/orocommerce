<?php

namespace Oro\Bundle\PricingBundle\Migrations\Schema\v1_11;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroPricingProductCurrencyIndex implements Migration
{
    /**
     * @inheritDoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addIndex($schema);
    }

    /**
     * Update Extended Entity
     */
    private function addIndex(Schema $schema)
    {
        $table = $schema->getTable('oro_price_product_combined');
        $table->addIndex(
            ['product_id', 'currency'],
            'oro_cmb_price_product_currency_idx'
        );
    }
}
