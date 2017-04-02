<?php

namespace Oro\Bundle\CMSBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;

class AddTextContentVariantTable implements
    Migration,
    OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroCmsTextContentVariantTable($schema);
        $this->createOroCmsTextContentVariantScopeTable($schema);

        /** Foreign keys generation **/
        $this->addOroCmsTextContentVariantForeignKeys($schema);
        $this->addOroCmsTextContentVariantScopeForeignKeys($schema);
    }

    /**
     * Create oro_cms_text_content_variant table
     *
     * @param Schema $schema
     */
    protected function createOroCmsTextContentVariantTable(Schema $schema)
    {
        $table = $schema->createTable('oro_cms_text_content_variant');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('content_block_id', 'integer', ['notnull' => false]);
        $table->addColumn('content', 'text', ['notnull' => false]);
        $table->addColumn('is_default', 'boolean', ['default' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['content_block_id'], 'IDX_17D032FABB5A68E3', []);
    }

    /**
     * Create oro_cms_txt_cont_variant_scope table
     *
     * @param Schema $schema
     */
    protected function createOroCmsTextContentVariantScopeTable(Schema $schema)
    {
        $table = $schema->createTable('oro_cms_txt_cont_variant_scope');
        $table->addColumn('variant_id', 'integer', []);
        $table->addColumn('scope_id', 'integer', []);
        $table->setPrimaryKey(['variant_id', 'scope_id']);
        $table->addIndex(['variant_id'], 'IDX_DA4264BA3B69A9AF', []);
        $table->addIndex(['scope_id'], 'IDX_DA4264BA682B5931', []);
    }

    /**
     * Add oro_cms_text_content_variant foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCmsTextContentVariantForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_cms_text_content_variant');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_cms_content_block'),
            ['content_block_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_cms_txt_cont_variant_scope foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCmsTextContentVariantScopeForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_cms_txt_cont_variant_scope');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_cms_text_content_variant'),
            ['variant_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_scope'),
            ['scope_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 2;
    }
}
