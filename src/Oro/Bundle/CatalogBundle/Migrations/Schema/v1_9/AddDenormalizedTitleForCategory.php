<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Schema\v1_9;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\CatalogBundle\Migrations\Schema\OroCatalogBundleInstaller;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddDenormalizedTitleForCategory implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addDenormalizedDefaultTitleColumn($schema);
    }

    protected function addDenormalizedDefaultTitleColumn(Schema $schema)
    {
        $table = $schema->getTable(OroCatalogBundleInstaller::ORO_CATALOG_CATEGORY_TABLE_NAME);

        $table->addColumn('title', 'string', ['length' => 255, 'notnull' => false]);
        $table->addIndex(['title'], 'idx_oro_product_default_title', []);
    }
}
