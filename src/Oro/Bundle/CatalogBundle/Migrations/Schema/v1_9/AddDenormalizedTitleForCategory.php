<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Schema\v1_9;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddDenormalizedTitleForCategory implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_catalog_category');
        $table->addColumn('title', 'string', ['length' => 255, 'notnull' => false]);
        $table->addIndex(['title'], 'idx_oro_product_default_title');
    }
}
