<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_15;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddBrandDefaultTitleMigration implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_brand');

        $table->addColumn('default_title', 'string', ['length' => 255, 'notnull' => false]);
        $table->addIndex(['default_title'], 'idx_oro_brand_default_title', []);
    }
}
