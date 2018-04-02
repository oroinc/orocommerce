<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Schema\v1_9;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\CatalogBundle\Migrations\Schema\OroCatalogBundleInstaller;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class MakeDefaultCategoryTitleNotNull implements
    Migration,
    OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable(OroCatalogBundleInstaller::ORO_CATALOG_CATEGORY_TABLE_NAME);
        $table->getColumn('title')->setNotnull(true);
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 30;
    }
}
