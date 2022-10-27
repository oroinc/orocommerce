<?php

namespace Oro\Bundle\CMSBundle\Migrations\Schema\v1_13;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareTrait;
use Oro\Bundle\CMSBundle\Migrations\Schema\OroCMSBundleInstaller;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddContentTemplateTable implements Migration, AttachmentExtensionAwareInterface
{
    use AttachmentExtensionAwareTrait;

    public function up(Schema $schema, QueryBag $queries): void
    {
        if (!$schema->hasTable('oro_cms_content_template')) {
            $table = $schema->createTable('oro_cms_content_template');
            $this->createColumns($schema, $table);
            $this->createWysiwygEditorColumns($table);
            $this->addForeignKeys($schema, $table);
        }
    }

    private function createColumns(Schema $schema, Table $table): void
    {
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('enabled', 'boolean', ['default' => true]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);

        $this->attachmentExtension->addImageRelation(
            $schema,
            'oro_cms_content_template',
            'previewImage',
            ['attachment' => ['acl_protected' => true, 'use_dam' => false]],
            OroCMSBundleInstaller::MAX_CONTENT_TEMPLATE_PREVIEW_IMAGE_SIZE_IN_MB
        );

        $table->setPrimaryKey(['id']);
    }

    private function createWysiwygEditorColumns(Table $table): void
    {
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
    }

    private function addForeignKeys(Schema $schema, Table $table): void
    {
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }
}
