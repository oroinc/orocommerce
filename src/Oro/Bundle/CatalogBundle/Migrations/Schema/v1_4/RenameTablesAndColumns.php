<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RenameTablesAndColumns implements Migration, RenameExtensionAwareInterface, OrderedMigrationInterface
{
    use RenameExtensionAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $extension = $this->renameExtension;

        // entity tables
        $extension->renameTable($schema, $queries, 'orob2b_catalog_category', 'oro_catalog_category');
        $extension->renameTable($schema, $queries, 'orob2b_catalog_category_title', 'oro_catalog_category_title');
        $extension->renameTable($schema, $queries, 'orob2b_category_to_product', 'oro_category_to_product');
        $extension->renameTable($schema, $queries, 'orob2b_catalog_cat_short_desc', 'oro_catalog_cat_short_desc');
        $extension->renameTable($schema, $queries, 'orob2b_catalog_cat_long_desc', 'oro_catalog_cat_long_desc');
        $extension->renameTable($schema, $queries, 'orob2b_category_def_prod_opts', 'oro_category_def_prod_opts');
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 0;
    }
}
