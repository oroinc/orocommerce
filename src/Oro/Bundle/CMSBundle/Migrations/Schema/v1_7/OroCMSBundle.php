<?php

namespace Oro\Bundle\CMSBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\CMSBundle\Entity\ImageSlide;
use Oro\Bundle\CMSBundle\Migrations\Data\ORM\LoadImageSlideTextAlignments;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroCMSBundle implements Migration
{
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
     *
     * @param Schema $schema
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
     *
     * @param Schema $schema
     */
    private function createOroCmsContentWidgetUsageTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_cms_content_widget_usage');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('content_widget_id', 'integer');
        $table->addColumn('entity_class', 'string', ['length' => 255]);
        $table->addColumn('entity_id', 'integer');
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_cms_image_slide table
     *
     * @param Schema $schema
     */
    private function createOroCmsImageSlideTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_cms_image_slide');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('content_widget_id', 'integer');
        $table->addColumn('order', 'integer', ['default' => 0]);
        $table->addColumn('url', 'string', ['length' => 255]);
        $table->addColumn('display_in_same_window', 'boolean', ['default' => true]);
        $table->addColumn('title', 'string', ['length' => 255]);
        $table->addColumn('text', 'text', ['notnull' => false]);
        $table->addColumn('main_image_id', 'integer');
        $table->addColumn('medium_image_id', 'integer', ['notnull' => false]);
        $table->addColumn('small_image_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['main_image_id'], 'UNIQ_E2EB2F2BE4873418');
        $table->addUniqueIndex(['medium_image_id'], 'UNIQ_E2EB2F2B442C36CF');
        $table->addUniqueIndex(['small_image_id'], 'UNIQ_E2EB2F2BD9E4E1BC');

        $textAlignmentEnumTable = $this->extendExtension->addEnumField(
            $schema,
            'oro_cms_image_slide',
            'text_alignment',
            ImageSlide::TEXT_ALIGNMENT_CODE,
            false,
            false,
            ['dataaudit' => ['auditable' => true]]
        );

        $textAlignmentOptions = new OroOptions();
        $textAlignmentOptions->set('enum', 'immutable_codes', LoadImageSlideTextAlignments::getDataKeys());

        $textAlignmentEnumTable->addOption(OroOptions::KEY, $textAlignmentOptions);
    }

    /**
     * Add oro_cms_content_widget foreign keys.
     *
     * @param Schema $schema
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
     *
     * @param Schema $schema
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
     *
     * @param Schema $schema
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
            $schema->getTable('oro_attachment_file'),
            ['main_image_id'],
            ['id'],
            ['onDelete' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_attachment_file'),
            ['medium_image_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_attachment_file'),
            ['small_image_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
    }
}
