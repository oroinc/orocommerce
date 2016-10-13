<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Schema\v1_5;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroCatalogBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addMaterializedPathField($schema, $queries);
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     */
    protected function addMaterializedPathField(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_catalog_category');
        $table->addColumn('materialized_path', 'string', ['length' => 255, 'notnull' => false]);
        
        $queries->addPostQuery(new UpdateMaterializedPathQuery());
    }
}
