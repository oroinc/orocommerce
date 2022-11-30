<?php

namespace Oro\Bundle\CMSBundle\Migrations\Schema\v1_12;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class CreateTabbedContentItemTable implements Migration
{
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->createTabbedContentItemTable($schema);
        $this->addTabbedContentItemForeignKeys($schema);
    }

    private function createTabbedContentItemTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_cms_tabbed_content_item');

        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->setPrimaryKey(['id']);

        $table->addColumn('content_widget_id', 'integer');
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('title', 'string', ['length' => 255]);
        $table->addColumn('item_order', 'integer', ['default' => 0]);
        $table->addColumn('content', 'wysiwyg', ['notnull' => false, 'comment' => '(DC2Type:wysiwyg)']);
        $table->addColumn(
            'content_style',
            'wysiwyg_style',
            [
                'notnull' => false,
                OroOptions::KEY => [
                    ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_HIDDEN,
                ],
            ]
        );
        $table->addColumn(
            'content_properties',
            'wysiwyg_properties',
            [
                'notnull' => false,
                OroOptions::KEY => [
                    ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_HIDDEN,
                ],
            ]
        );
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
    }

    private function addTabbedContentItemForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_cms_tabbed_content_item');
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
