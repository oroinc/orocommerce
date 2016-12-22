<?php

namespace Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroFrontendTestFrameworkBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->createTable('oro_test_content_variant');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_page_product', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addForeignKeyConstraint('oro_product', ['product_page_product'], ['id']);
    }

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_0';
    }
}
