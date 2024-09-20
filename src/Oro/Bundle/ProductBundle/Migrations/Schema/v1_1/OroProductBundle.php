<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\RemoveOutdatedEnumFieldQuery;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\OutdatedExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\OutdatedExtendExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroProductBundle implements Migration, OutdatedExtendExtensionAwareInterface, OrderedMigrationInterface
{
    use OutdatedExtendExtensionAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function getOrder(): int
    {
        return 10;
    }

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->renameStatusEnumToStatusString($schema, $queries);
        $this->removeVisibilityEnum($schema, $queries);
        $this->updateOroProductTable($schema, $queries);
        $this->createOroProductVariantLinkTable($schema);
        $this->addOroProductVariantLinkForeignKeys($schema);
    }

    private function createOroProductVariantLinkTable(Schema $schema): void
    {
        $table = $schema->createTable('orob2b_product_variant_link');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => true]);
        $table->addColumn('parent_product_id', 'integer', ['notnull' => true]);
        $table->addColumn('visible', 'boolean', ['default' => true]);
        $table->setPrimaryKey(['id']);
    }

    private function addOroProductVariantLinkForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('orob2b_product_variant_link');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product'),
            ['product_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product'),
            ['parent_product_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    private function updateOroProductTable(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('orob2b_product');
        $table->addColumn('has_variants', 'boolean', ['default' => false]);
        $table->addColumn('variant_fields', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->addIndex(['sku'], 'idx_orob2b_product_sku');

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

    private function renameStatusEnumToStatusString(Schema $schema, QueryBag $queries): void
    {
        // create new status column
        $table = $schema->getTable('orob2b_product');
        $table->addColumn('status', 'string', ['length' => 16, 'notnull' => false]);

        // move data from old to new column
        $queries->addPostQuery('UPDATE orob2b_product SET status = status_id');

        // drop status enum table
        $enumStatusTable = $this->outdatedExtendExtension->generateEnumTableName('prod_status');
        if ($schema->hasTable($enumStatusTable)) {
            $schema->dropTable($enumStatusTable);
        }
    }

    private function removeVisibilityEnum(Schema $schema, QueryBag $queries): void
    {
        // drop visibility enum field
        $productTable = $schema->getTable('orob2b_product');
        if ($productTable->hasColumn('visibility_id')) {
            $productTable->dropColumn('visibility_id');
        }

        // drop visibility enum table
        $enumVisibilityTable = $this->outdatedExtendExtension->generateEnumTableName('prod_visibility');
        if ($schema->hasTable($enumVisibilityTable)) {
            $schema->dropTable($enumVisibilityTable);
        }

        // remove visibility enum field data
        $queries->addQuery(new RemoveOutdatedEnumFieldQuery('Oro\Bundle\ProductBundle\Entity\Product', 'visibility'));
    }
}
