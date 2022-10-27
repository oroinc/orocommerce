<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_17;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class UpdateProductSkuUniqueIndex implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_product');
        $indexName = 'uniq_oro_product_sku';

        if ($table->hasIndex($indexName)) {
            $table->dropIndex($indexName);
        }

        $table->addUniqueIndex(['sku', 'organization_id'], 'uidx_oro_product_sku_organization');
    }
}
