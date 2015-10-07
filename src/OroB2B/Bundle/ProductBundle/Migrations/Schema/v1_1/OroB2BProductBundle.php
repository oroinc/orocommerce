<?php

namespace OroB2B\Bundle\ProductBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BProductBundle implements Migration, ExtendExtensionAwareInterface
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
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->renameStatusEnumToStatusString($schema, $queries);
        $this->updateOroB2BProductTable($schema);
        $this->createOroB2BProductVariantLinkTable($schema);
        $this->addOroB2BProductVariantLinkForeignKeys($schema);
    }

    /**
     * @param Schema $schema
     */
    protected function createOroB2BProductVariantLinkTable(Schema $schema)
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
    protected function addOroB2BProductVariantLinkForeignKeys(Schema $schema)
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
     */
    protected function updateOroB2BProductTable(Schema $schema)
    {
        $table = $schema->getTable(self::PRODUCT_TABLE_NAME);
        $table->addColumn('has_variants', 'boolean', ['default' => false]);
        $table->addColumn('variant_fields', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     */
    protected function renameStatusEnumToStatusString(Schema $schema, QueryBag $queries)
    {
        // rename status column
        $productTable = $schema->getTable('orob2b_product');
        $productTable->renameColumn('status_id', 'status');
        $productTable->changeColumn('status', ['type' => 'string', 'length' => 16]);

        // drop status enum table
        $enumStatusTable = $this->extendExtension->getNameGenerator()->generateEnumTableName('prod_status');
        if ($schema->hasTable($enumStatusTable)) {
            $schema->dropTable($enumStatusTable);
        }

        // remove status enum field data
        $queries->addQuery(new RemoveStatusEnumQuery());
    }
}
