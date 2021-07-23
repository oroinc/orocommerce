<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_14;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ProductBundle\Migrations\Schema\OroProductBundleInstaller;

class AddDefaultProductNameColumn implements
    Migration,
    OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 10;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addDenormalizedDefaultNameColumn($schema);
        $this->addDenormalizedDefaultNameColumnUppercase($schema);
    }

    protected function addDenormalizedDefaultNameColumn(Schema $schema)
    {
        $table = $schema->getTable(OroProductBundleInstaller::PRODUCT_TABLE_NAME);

        $table->addColumn('name', 'string', ['length' => 255, 'notnull' => false]);
        $table->addIndex(['name'], 'idx_oro_product_default_name', []);
    }

    protected function addDenormalizedDefaultNameColumnUppercase(Schema $schema)
    {
        $table = $schema->getTable(OroProductBundleInstaller::PRODUCT_TABLE_NAME);

        $table->addColumn('name_uppercase', 'string', ['length' => 255, 'notnull' => false]);
        $table->addIndex(['name_uppercase'], 'idx_oro_product_default_name_uppercase', []);
    }
}
