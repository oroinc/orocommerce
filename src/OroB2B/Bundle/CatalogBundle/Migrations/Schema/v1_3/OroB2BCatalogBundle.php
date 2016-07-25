<?php

namespace OroB2B\Bundle\CatalogBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BCatalogBundle implements Migration, RenameExtensionAwareInterface
{
    const ORO_B2B_CATALOG_CATEGORY_TABLE_NAME = 'orob2b_catalog_category';
    const ORO_B2B_CATEGORY_DEFAULT_PRODUCT_OPTIONS_TABLE_NAME = 'orob2b_category_def_prod_opts';
    const ORO_B2B_PRODUCT_UNIT_TABLE_NAME = 'orob2b_product_unit';
    
    /**
     * @var RenameExtension
     */
    protected $renameExtension;

    /**
     * {@inheritdoc}
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createConstraint(
            $schema,
            $queries,
            'orob2b_catalog_cat_long_desc',
            'oro_fallback_localization_val',
            ['localized_value_id']
        );
        $this->createConstraint(
            $schema,
            $queries,
            'orob2b_catalog_cat_short_desc',
            'oro_fallback_localization_val',
            ['localized_value_id']
        );
        $this->createConstraint(
            $schema,
            $queries,
            'orob2b_catalog_category_title',
            'oro_fallback_localization_val',
            ['localized_value_id']
        );
        $this->createOroB2BCategoryDefaultProductOptionsTable($schema);
        $this->updateOroB2BCategoryTable($schema);
        $this->addOroB2BCategoryDefaultProductOptionsForeignKeys($schema);
        $this->addOroB2BCategoryForeignKeys($schema);
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     * @param string $tableName
     * @param string $foreignTable
     * @param array $fields
     */
    protected function createConstraint(Schema $schema, QueryBag $queries, $tableName, $foreignTable, array $fields)
    {
        $this->renameExtension->addForeignKeyConstraint(
            $schema,
            $queries,
            $tableName,
            $foreignTable,
            $fields,
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * @param Schema $schema
     */
    protected function createOroB2BCategoryDefaultProductOptionsTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_B2B_CATEGORY_DEFAULT_PRODUCT_OPTIONS_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_unit_code', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('product_unit_precision', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * @param Schema $schema
     */
    protected function updateOroB2BCategoryTable(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_CATALOG_CATEGORY_TABLE_NAME);
        $table->addColumn('default_product_options_id', 'integer', ['notnull' => false]);
        $table->addUniqueIndex(['default_product_options_id']);
    }

    /**
     * @param Schema $schema
     */
    protected function addOroB2BCategoryDefaultProductOptionsForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_CATEGORY_DEFAULT_PRODUCT_OPTIONS_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_B2B_PRODUCT_UNIT_TABLE_NAME),
            ['product_unit_code'],
            ['code'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * @param Schema $schema
     */
    protected function addOroB2BCategoryForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_CATALOG_CATEGORY_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_B2B_CATEGORY_DEFAULT_PRODUCT_OPTIONS_TABLE_NAME),
            ['default_product_options_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
