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
        $this->createOroContentVariantTable($schema);
        $this->createOroContentNodeTable($schema);
        $this->createOroContentNodeSlugTable($schema);
        $this->createOroContentNodeTitleTable($schema);
        $this->createOroContentNodeToSlugTable($schema);

        /** Foreign keys generation **/
        $this->addOroContentNodeForeignKeys($schema);
        $this->addOroContentNodeSlugForeignKeys($schema);
        $this->addOroContentNodeTitleForeignKeys($schema);
        $this->addOroContentNodeToSlugForeignKeys($schema);
        $this->addOroContentVariantForeignKeys($schema);
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
     * Create oro_web_catalog_variant table
     *
     * @param Schema $schema
     */
    protected function createOroContentVariantTable(Schema $schema)
    {
        $table = $schema->createTable('oro_web_catalog_variant');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('node_id', 'integer', ['notnull' => false]);
        $table->addColumn('type', 'string', ['length' => 255]);
        $table->addColumn('system_page_route', 'string', ['length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['node_id']);
    }

    /**
     * Create oro_web_catalog_content_node table
     *
     * @param Schema $schema
     */
    protected function createOroContentNodeTable(Schema $schema)
    {
        $table = $schema->createTable('oro_web_catalog_content_node');
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
        $this->noteExtension->addNoteAssociation($schema, 'oro_web_catalog_content_node');
        $this->attachmentExtension->addImageRelation(
            $schema,
            'oro_web_catalog_content_node',
            'image'
        );
    }

    /**
     * Create oro_web_catalog_node_slug table
     *
     * @param Schema $schema
     */
    protected function createOroContentNodeSlugTable(Schema $schema)
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
    protected function createOroContentNodeTitleTable(Schema $schema)
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
    protected function createOroContentNodeToSlugTable(Schema $schema)
    {
        $table = $schema->createTable('oro_web_catalog_node_to_slug');
        $table->addColumn('node_id', 'integer', []);
        $table->addColumn('slug_id', 'integer', []);
        $table->setPrimaryKey(['node_id', 'slug_id']);
        $table->addUniqueIndex(['slug_id']);
    }

    /**
     * Add oro_web_catalog_content_node foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroContentNodeForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_web_catalog_content_node');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_web_catalog_content_node'),
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
    protected function addOroContentNodeSlugForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_web_catalog_node_slug');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_web_catalog_content_node'),
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
    protected function addOroContentNodeTitleForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_web_catalog_node_title');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_web_catalog_content_node'),
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
    protected function addOroContentNodeToSlugForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_web_catalog_node_to_slug');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_redirect_slug'),
            ['slug_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_web_catalog_content_node'),
            ['node_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_web_catalog_variant foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroContentVariantForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_web_catalog_variant');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_web_catalog_content_node'),
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
