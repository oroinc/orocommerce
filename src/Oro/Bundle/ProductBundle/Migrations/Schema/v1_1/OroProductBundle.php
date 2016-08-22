<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityConfigBundle\Migration\RemoveEnumFieldQuery;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;

class OroProductBundle implements Migration, ExtendExtensionAwareInterface, OrderedMigrationInterface
{
    const PRODUCT_VARIANT_LINK_TABLE_NAME = 'orob2b_product_variant_link';
    const PRODUCT_TABLE_NAME = 'orob2b_product';

    /**
     * @var ExtendExtension
     */
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
    public function getOrder()
    {
        return 10;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->renameStatusEnumToStatusString($schema, $queries);
        $this->removeVisibilityEnum($schema, $queries);
        $this->updateOroProductTable($schema, $queries);
        $this->createOroProductVariantLinkTable($schema);
        $this->addOroProductVariantLinkForeignKeys($schema);
    }

    /**
     * @param Schema $schema
     */
    protected function createOroProductVariantLinkTable(Schema $schema)
    {
        $table = $schema->createTable(self::PRODUCT_VARIANT_LINK_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => true]);
        $table->addColumn('parent_product_id', 'integer', ['notnull' => true]);
        $table->addColumn('visible', 'boolean', ['default' => true]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * @param Schema $schema
     */
    protected function addOroProductVariantLinkForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::PRODUCT_VARIANT_LINK_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::PRODUCT_TABLE_NAME),
            ['product_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(self::PRODUCT_TABLE_NAME),
            ['parent_product_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     */
    protected function updateOroProductTable(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable(self::PRODUCT_TABLE_NAME);
        $table->addColumn('has_variants', 'boolean', ['default' => false]);
        $table->addColumn('variant_fields', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->addIndex(['sku'], 'idx_orob2b_product_sku', []);

        $queries->addQuery(
            new UpdateEntityConfigFieldValueQuery(
                'Oro\Bundle\ProductBundle\Entity\Product',
                'inventory_status',
                'importexport',
                'order',
                '25'
            )
        );

        $queries->addQuery(
            new UpdateEntityConfigFieldValueQuery(
                'Oro\Bundle\ProductBundle\Entity\Product',
                'image',
                'importexport',
                'excluded',
                true
            )
        );
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     */
    protected function renameStatusEnumToStatusString(Schema $schema, QueryBag $queries)
    {
        // create new status column
        $table = $schema->getTable(self::PRODUCT_TABLE_NAME);
        $table->addColumn('status', 'string', ['length' => 16, 'notnull' => false]);

        // move data from old to new column
        $queries->addPostQuery(sprintf('UPDATE %s SET status = status_id', self::PRODUCT_TABLE_NAME));

        // drop status enum table
        $enumStatusTable = $this->extendExtension->getNameGenerator()->generateEnumTableName('prod_status');
        if ($schema->hasTable($enumStatusTable)) {
            $schema->dropTable($enumStatusTable);
        }
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     */
    protected function removeVisibilityEnum(Schema $schema, QueryBag $queries)
    {
        // drop visibility enum field
        $productTable = $schema->getTable('orob2b_product');
        if ($productTable->hasColumn('visibility_id')) {
            $productTable->dropColumn('visibility_id');
        }
        
        // drop visibility enum table
        $enumVisibilityTable = $this->extendExtension->getNameGenerator()->generateEnumTableName('prod_visibility');
        if ($schema->hasTable($enumVisibilityTable)) {
            $schema->dropTable($enumVisibilityTable);
        }

        // remove visibility enum field data
        $queries->addQuery(new RemoveEnumFieldQuery('Oro\Bundle\ProductBundle\Entity\Product', 'visibility'));
    }
}
