<?php

namespace Oro\Bundle\CMSBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;
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
        $this->addOroCmsContentWidgetForeignKeys($schema);
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
}
