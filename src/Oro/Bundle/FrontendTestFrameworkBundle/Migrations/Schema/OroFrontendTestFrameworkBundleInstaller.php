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
        $table = $schema->createTable('oro_test_content_variant');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_page_product', 'integer', ['notnull' => false]);
        $table->addColumn('category_page_category', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addForeignKeyConstraint('oro_product', ['product_page_product'], ['id']);
        $table->addForeignKeyConstraint('oro_catalog_category', ['category_page_category'], ['id']);
        $this->addVariantFieldToProduct($schema);
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
                    'attribute' => ['is_attribute' => true],
                    'importexport' => ['excluded' => true]
                ]
            );
        }
    }
}
