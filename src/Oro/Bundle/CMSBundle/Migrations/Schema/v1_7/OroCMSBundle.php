<?php

namespace Oro\Bundle\CMSBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareTrait;
use Oro\Bundle\CMSBundle\Entity\ImageSlide;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroCMSBundle implements Migration, AttachmentExtensionAwareInterface
{
    use AttachmentExtensionAwareTrait;

    const MAX_IMAGE_SLIDE_MAIN_IMAGE_SIZE_IN_MB = 10;
    const MAX_IMAGE_SLIDE_MEDIUM_IMAGE_SIZE_IN_MB = 10;
    const MAX_IMAGE_SLIDE_SMALL_IMAGE_SIZE_IN_MB = 10;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createOroCmsContentWidgetTable($schema);
        $this->createOroCmsContentWidgetUsageTable($schema);
        $this->createOroCmsImageSlideTable($schema);

        $this->addOroCmsContentWidgetForeignKeys($schema);
        $this->addOroCmsContentWidgetUsageForeignKeys($schema);
        $this->addOroCmsImageSlideForeignKeys($schema);
    }

    /**
     * Create oro_cms_content_widget table
     */
    private function createOroCmsContentWidgetTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_cms_content_widget');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('description', 'text', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('widget_type', 'string', ['length' => 255]);
        $table->addColumn('template', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('settings', 'array');
        $table->addUniqueIndex(['organization_id', 'name'], 'uidx_oro_cms_content_widget');
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_cms_content_widget_usage table
     */
    private function createOroCmsContentWidgetUsageTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_cms_content_widget_usage');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('content_widget_id', 'integer');
        $table->addColumn('entity_class', 'string', ['length' => 255]);
        $table->addColumn('entity_id', 'integer');
        $table->addColumn('entity_field', 'string', ['notnull' => false, 'length' => 50]);
        $table->addUniqueIndex(
            ['entity_class', 'entity_id', 'entity_field', 'content_widget_id'],
            'uidx_oro_cms_content_widget_usage'
        );
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_cms_image_slide table
     */
    private function createOroCmsImageSlideTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_cms_image_slide');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('content_widget_id', 'integer');
        $table->addColumn('slide_order', 'integer', ['default' => 0]);
        $table->addColumn('url', 'string', ['length' => 255]);
        $table->addColumn('display_in_same_window', 'boolean', ['default' => true]);
        $table->addColumn('title', 'string', ['length' => 255]);
        $table->addColumn('text', 'text', ['notnull' => false]);
        $table->addColumn('text_alignment', 'string', ['length' => 20, 'default' => ImageSlide::TEXT_ALIGNMENT_CENTER]);

        $this->attachmentExtension->addImageRelation(
            $schema,
            'oro_cms_image_slide',
            'mainImage',
            ['attachment' => ['acl_protected' => false, 'use_dam' => true]],
            self::MAX_IMAGE_SLIDE_MAIN_IMAGE_SIZE_IN_MB
        );
        $this->attachmentExtension->addImageRelation(
            $schema,
            'oro_cms_image_slide',
            'mediumImage',
            ['attachment' => ['acl_protected' => false, 'use_dam' => true]],
            self::MAX_IMAGE_SLIDE_MEDIUM_IMAGE_SIZE_IN_MB
        );
        $this->attachmentExtension->addImageRelation(
            $schema,
            'oro_cms_image_slide',
            'smallImage',
            ['attachment' => ['acl_protected' => false, 'use_dam' => true]],
            self::MAX_IMAGE_SLIDE_SMALL_IMAGE_SIZE_IN_MB
        );

        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add oro_cms_content_widget foreign keys.
     */
    private function addOroCmsContentWidgetForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_cms_content_widget');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
    }

    /**
     * Add oro_cms_content_widget_usage foreign keys.
     */
    private function addOroCmsContentWidgetUsageForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_cms_content_widget_usage');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_cms_content_widget'),
            ['content_widget_id'],
            ['id'],
            ['onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_cms_image_slide foreign keys.
     */
    private function addOroCmsImageSlideForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_cms_image_slide');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_cms_content_widget'),
            ['content_widget_id'],
            ['id'],
            ['onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
    }
}
