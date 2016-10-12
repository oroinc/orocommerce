<?php

namespace Oro\Bundle\WebCatalogBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtension;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtension;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtensionAwareInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class OroWebCatalogBundleInstaller implements
    Installation,
    NoteExtensionAwareInterface,
    AttachmentExtensionAwareInterface
{
    /** @var NoteExtension */
    protected $noteExtension;
    
    /** @var AttachmentExtension */
    protected $attachmentExtension;
    
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_0';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroWebCatalogTable($schema);
        $this->createOroWebCatalogPageTable($schema);
        $this->createOroWebCatalogNodeTable($schema);
        $this->createOroWebCatalogNodeSlugTable($schema);
        $this->createOroWebCatalogNodeTitleTable($schema);
        $this->createOroWebCatalogNodeToSlugTable($schema);

        /** Foreign keys generation **/
        $this->addOroWebCatalogNodeForeignKeys($schema);
        $this->addOroWebCatalogNodeSlugForeignKeys($schema);
        $this->addOroWebCatalogNodeTitleForeignKeys($schema);
        $this->addOroWebCatalogNodeToSlugForeignKeys($schema);
        $this->addOroWebCatalogPageForeignKeys($schema);
    }

    /**
     * Create oro_web_catalog table
     *
     * @param Schema $schema
     */
    protected function createOroWebCatalogTable(Schema $schema)
    {
        $table = $schema->createTable('oro_web_catalog');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('description', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_web_catalog_page table
     *
     * @param Schema $schema
     */
    protected function createOroWebCatalogPageTable(Schema $schema)
    {
        $table = $schema->createTable('oro_web_catalog_page');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('node_id', 'integer', ['notnull' => false]);
        $table->addColumn('type', 'string', ['length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['node_id']);
    }

    /**
     * Create oro_web_catalog_node table
     *
     * @param Schema $schema
     */
    protected function createOroWebCatalogNodeTable(Schema $schema)
    {
        $table = $schema->createTable('oro_web_catalog_node');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('parent_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('materialized_path', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('tree_left', 'integer', []);
        $table->addColumn('tree_level', 'integer', []);
        $table->addColumn('tree_right', 'integer', []);
        $table->addColumn('tree_root', 'integer', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $this->noteExtension->addNoteAssociation($schema, 'oro_web_catalog_node');
        $this->attachmentExtension->addImageRelation(
            $schema,
            'oro_web_catalog_node',
            'image'
        );
    }

    /**
     * Create oro_web_catalog_node_slug table
     *
     * @param Schema $schema
     */
    protected function createOroWebCatalogNodeSlugTable(Schema $schema)
    {
        $table = $schema->createTable('oro_web_catalog_node_slug');
        $table->addColumn('node_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['node_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id']);
    }

    /**
     * Create oro_web_catalog_node_title table
     *
     * @param Schema $schema
     */
    protected function createOroWebCatalogNodeTitleTable(Schema $schema)
    {
        $table = $schema->createTable('oro_web_catalog_node_title');
        $table->addColumn('node_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['node_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id']);
    }

    /**
     * Create oro_web_catalog_node_to_slug table
     *
     * @param Schema $schema
     */
    protected function createOroWebCatalogNodeToSlugTable(Schema $schema)
    {
        $table = $schema->createTable('oro_web_catalog_node_to_slug');
        $table->addColumn('node_id', 'integer', []);
        $table->addColumn('slug_id', 'integer', []);
        $table->setPrimaryKey(['node_id', 'slug_id']);
        $table->addUniqueIndex(['slug_id']);
    }

    /**
     * Add oro_web_catalog_node foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroWebCatalogNodeForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_web_catalog_node');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_web_catalog_node'),
            ['parent_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_web_catalog_node_slug foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroWebCatalogNodeSlugForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_web_catalog_node_slug');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_web_catalog_node'),
            ['node_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_web_catalog_node_title foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroWebCatalogNodeTitleForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_web_catalog_node_title');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_web_catalog_node'),
            ['node_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_web_catalog_node_to_slug foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroWebCatalogNodeToSlugForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_web_catalog_node_to_slug');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_redirect_slug'),
            ['slug_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_web_catalog_node'),
            ['node_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_web_catalog_page foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroWebCatalogPageForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_web_catalog_page');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_web_catalog_node'),
            ['node_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setNoteExtension(NoteExtension $noteExtension)
    {
        $this->noteExtension = $noteExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function setAttachmentExtension(AttachmentExtension $attachmentExtension)
    {
        $this->attachmentExtension = $attachmentExtension;
    }
}
