<?php

namespace OroB2B\Bundle\ProductBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BProductBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('orob2b_product');
        $table->addIndex(['created_at'], 'idx_orob2b_product_created_at', []);
        $table->addIndex(['updated_at'], 'idx_orob2b_product_updated_at', []);
    }
}
