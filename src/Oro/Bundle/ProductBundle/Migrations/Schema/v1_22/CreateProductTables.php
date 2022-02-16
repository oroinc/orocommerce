<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_22;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class CreateProductTables implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getOrder(): int
    {
        return 10;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        if ($schema->hasTable('oro_product_prod_name')) {
            return;
        }

        $this->createOroProductNameTable($schema);
        $this->addOroProductNameForeignKeys($schema);

        $this->createOroProductShortDescriptionTable($schema);
        $this->addOroProductShortDescriptionForeignKeys($schema);

        $this->createOroProductDescriptionTable($schema);
        $this->addOroProductDescriptionForeignKeys($schema);
    }

    /**
     * Create oro_product_prod_name table
     */
    private function createOroProductNameTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_product_prod_name');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => false]);
        $table->addColumn('localization_id', 'integer', ['notnull' => false]);
        $table->addColumn('fallback', 'string', ['notnull' => false, 'length' => 64]);
        $table->addColumn('string', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['fallback'], 'idx_product_prod_name_fallback', []);
        $table->addIndex(['string'], 'idx_product_prod_name_string', []);
    }

    /**
     * Add oro_product_prod_name foreign keys.
     */
    private function addOroProductNameForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_product_prod_name');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['product_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_localization'),
            ['localization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Create oro_product_prod_s_descr table
     */
    private function createOroProductShortDescriptionTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_product_prod_s_descr');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => false]);
        $table->addColumn('localization_id', 'integer', ['notnull' => false]);
        $table->addColumn('fallback', 'string', ['notnull' => false, 'length' => 64]);
        $table->addColumn('text', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['fallback'], 'idx_product_prod_s_descr_fallback', []);
    }

    /**
     * Add oro_product_prod_s_descr foreign keys.
     */
    private function addOroProductShortDescriptionForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_product_prod_s_descr');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['product_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_localization'),
            ['localization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Create oro_product_prod_descr table
     */
    private function createOroProductDescriptionTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_product_prod_descr');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => false]);
        $table->addColumn('localization_id', 'integer', ['notnull' => false]);
        $table->addColumn('fallback', 'string', ['notnull' => false, 'length' => 64]);
        $table->addColumn('wysiwyg', 'wysiwyg', ['notnull' => false, 'comment' => '(DC2Type:wysiwyg)']);
        $table->addColumn('wysiwyg_style', 'wysiwyg_style', ['notnull' => false]);
        $table->addColumn('wysiwyg_properties', 'wysiwyg_properties', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['fallback'], 'idx_product_prod_descr_fallback', []);
    }

    /**
     * Add oro_product_prod_descr foreign keys.
     */
    private function addOroProductDescriptionForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_product_prod_descr');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['product_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_localization'),
            ['localization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}
