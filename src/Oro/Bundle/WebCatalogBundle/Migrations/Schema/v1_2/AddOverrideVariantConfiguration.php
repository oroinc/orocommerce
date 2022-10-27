<?php

namespace Oro\Bundle\WebCatalogBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddOverrideVariantConfiguration implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_web_catalog_variant');
        $table->addColumn('override_variant_configuration', 'boolean', ['default' => false]);
    }
}
