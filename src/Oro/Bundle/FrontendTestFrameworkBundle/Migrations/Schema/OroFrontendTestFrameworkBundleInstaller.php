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
        $this->createTestWebCatalog($schema);
        $this->createTestContentNode($schema);
        $this->createTestContentVariant($schema);
    }

    /**
     * @param Schema $schema
     */
    private function createTestContentVariant(Schema $schema)
    {
        $table = $schema->createTable('oro_test_content_variant');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_page_product', 'integer', ['notnull' => false]);
        $table->addColumn('category_page_category', 'integer', ['notnull' => false]);
        $table->addColumn('product_collection_segment', 'integer', ['notnull' => false]);
        $table->addColumn('node', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addForeignKeyConstraint('oro_product', ['product_page_product'], ['id']);
        $table->addForeignKeyConstraint('oro_catalog_category', ['category_page_category'], ['id']);
        $table->addForeignKeyConstraint('oro_segment', ['product_collection_segment'], ['id']);
        $table->addForeignKeyConstraint('oro_test_content_node', ['node'], ['id']);
    }

    /**
     * @param Schema $schema
     */
    private function createTestContentNode(Schema $schema)
    {
        $table = $schema->createTable('oro_test_content_node');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('web_catalog', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addForeignKeyConstraint('oro_test_web_catalog', ['web_catalog'], ['id']);
    }

    /**
     * @param Schema $schema
     */
    private function createTestWebCatalog(Schema $schema)
    {
        $table = $schema->createTable('oro_test_web_catalog');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_0';
    }
}
