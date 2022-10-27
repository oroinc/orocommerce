<?php

namespace Oro\Bundle\SEOBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroSEOBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createOroWebCatalogProductLimitTable($schema);
    }

    /**
     * Create oro_web_catalog_product_limit table
     */
    private function createOroWebCatalogProductLimitTable(Schema $schema)
    {
        $table = $schema->createTable('oro_web_catalog_product_limit');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', []);
        $table->addColumn('version', 'integer', []);
        $table->setPrimaryKey(['id']);
    }
}
