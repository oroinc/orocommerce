<?php

namespace OroB2B\Bundle\CatalogBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtension;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtensionAwareInterface;

class OroB2BCatalogBundle implements Migration, NoteExtensionAwareInterface
{
    const CATEGORY_TABLE_NAME = 'orob2b_catalog_category';
    const CATEGORY_TO_PRODUCT_TABLE_NAME = 'orob2b_category_to_product';
    const PRODUCT_TABLE = 'orob2b_product';

    /** @var  NoteExtension */
    protected $noteExtension;

    /**
     * Sets the NoteExtension
     *
     * @param NoteExtension $noteExtension
     */
    public function setNoteExtension(NoteExtension $noteExtension)
    {
        $this->noteExtension = $noteExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOrob2BCategoryToProductTable($schema);

        /** Foreign keys generation **/
        $this->addOrob2BCategoryToProductForeignKeys($schema);

        $this->addNoteToCategory($schema);
    }

    /**
     * Create orob2b_category_to_product table
     *
     * @param Schema $schema
     */
    protected function createOrob2BCategoryToProductTable(Schema $schema)
    {
        $table = $schema->createTable(static::CATEGORY_TO_PRODUCT_TABLE_NAME);
        $table->addColumn('category_id', 'integer', []);
        $table->addColumn('product_id', 'integer', []);
        $table->setPrimaryKey(['category_id', 'product_id']);
        $table->addUniqueIndex(['product_id'], 'UNIQ_FB6D81664584665A');
        $table->addIndex(['category_id'], 'IDX_FB6D816612469DE2', []);
    }

    /**
     * Add orob2b_category_to_product foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BCategoryToProductForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(static::CATEGORY_TO_PRODUCT_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(static::CATEGORY_TABLE_NAME),
            ['category_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(static::PRODUCT_TABLE),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * @param Schema $schema
     */
    private function addNoteToCategory(Schema $schema)
    {
        $this->noteExtension->addNoteAssociation($schema, static::CATEGORY_TABLE_NAME);
    }
}
