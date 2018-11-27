<?php

namespace Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroFrontendTestFrameworkBundleInstaller implements Installation, ExtendExtensionAwareInterface
{
    const VARIANT_FIELD_NAME = 'test_variant_field';
    const VARIANT_FIELD_CODE = 'variant_field_code';

    const MULTIENUM_FIELD_NAME = 'multienum_field';
    const MULTIENUM_FIELD_CODE = 'multienum_code';

    /** @var ExtendExtension */
    protected $extendExtension;

    /**
     * {@inheritdoc}
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createTestWebCatalog($schema);
        $this->createTestContentNode($schema);
        $this->createTestContentVariant($schema);
        $this->addVariantFieldToProduct($schema);
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
        $table->addForeignKeyConstraint(
            'oro_segment',
            ['product_collection_segment'],
            ['id'],
            ['onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint('oro_test_content_node', ['node'], ['id'], ['onDelete' => 'CASCADE']);
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

    /**
     * @param Schema $schema
     */
    private function addVariantFieldToProduct(Schema $schema)
    {
        if ($schema->hasTable('oro_product')) {
            $table = $schema->getTable('oro_product');

            $this->extendExtension->addEnumField(
                $schema,
                $table,
                self::VARIANT_FIELD_NAME,
                self::VARIANT_FIELD_CODE,
                false,
                false,
                [
                    'extend' => ['owner' => ExtendScope::OWNER_CUSTOM],
                    'attribute' => ['is_attribute' => true, 'searchable' => true, 'filterable' => true],
                    'importexport' => ['excluded' => true]
                ]
            );

            $this->extendExtension->addEnumField(
                $schema,
                $table,
                self::MULTIENUM_FIELD_NAME,
                self::MULTIENUM_FIELD_CODE,
                true,
                false,
                [
                    'extend' => ['owner' => ExtendScope::OWNER_CUSTOM],
                    'attribute' => ['is_attribute' => true, 'searchable' => true, 'filterable' => true],
                    'importexport' => ['excluded' => true]
                ]
            );
        }
    }
}
