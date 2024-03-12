<?php

namespace Oro\Bundle\CMSBundle\Migrations\Schema\v1_14;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddContentWidgetLabelTable implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->createOroCmsContentWidgetLabelTable($schema);
        $this->addOroCmsContentWidgetLabelForeignKeys($schema);
    }

    /**
     * Create oro_cms_content_widget_label table
     */
    private function createOroCmsContentWidgetLabelTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_cms_content_widget_label');
        $table->addColumn('content_widget_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['content_widget_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id']);
    }

    /**
     * Add oro_cms_content_widget_label foreign keys.
     */
    private function addOroCmsContentWidgetLabelForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_cms_content_widget_label');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_cms_content_widget'),
            ['content_widget_id'],
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
}
