<?php

namespace Oro\Bundle\CMSBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtension;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroCMSBundleInstaller implements Installation, AttachmentExtensionAwareInterface, ExtendExtensionAwareInterface
{
    const CMS_LOGIN_PAGE_TABLE = 'oro_cms_login_page';
    const MAX_LOGO_IMAGE_SIZE_IN_MB = 10;
    const MAX_BACKGROUND_IMAGE_SIZE_IN_MB = 10;

    /**
     * @var AttachmentExtension
     */
    protected $attachmentExtension;

    /**
     * @var ExtendExtension
     */
    protected $extendExtension;

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_2';
    }

    /**
     * {@inheritdoc}
     */
    public function setAttachmentExtension(AttachmentExtension $attachmentExtension)
    {
        $this->attachmentExtension = $attachmentExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroCmsPageTable($schema);
        $this->createOroCmsPageToSlugTable($schema);
        $this->createOroCmsLoginPageTable($schema);

        /** Foreign keys generation **/
        $this->addOroCmsPageForeignKeys($schema);
        $this->addOroCmsPageToSlugForeignKeys($schema);

        $this->addImageAssociations($schema);
        
        $this->addWebCatalogPageTypes($schema);
    }

    /**
     * Create oro_cms_page table
     *
     * @param Schema $schema
     */
    protected function createOroCmsPageTable(Schema $schema)
    {
        $table = $schema->createTable('oro_cms_page');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('current_slug_id', 'integer', ['notnull' => false]);

//@TODO will be handled in BB-4943
//        $table->addColumn('parent_id', 'integer', ['notnull' => false]);

        $table->addColumn('title', 'string', ['length' => 255]);

        //@TODO will be handled in BB-4943
        $table->addColumn('content', 'text', ['notnull' => false]);
        //$table->addColumn('content', 'text', []);

//@TODO will be handled in BB-4943
//        $table->addColumn('tree_left', 'integer', []);
//        $table->addColumn('tree_level', 'integer', []);
//        $table->addColumn('tree_right', 'integer', []);
//        $table->addColumn('tree_root', 'integer', ['notnull' => false]);

        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->addUniqueIndex(['current_slug_id']);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_cms_page_to_slug table
     *
     * @param Schema $schema
     */
    protected function createOroCmsPageToSlugTable(Schema $schema)
    {
        $table = $schema->createTable('oro_cms_page_to_slug');
        $table->addColumn('page_id', 'integer', []);
        $table->addColumn('slug_id', 'integer', []);
        $table->setPrimaryKey(['page_id', 'slug_id']);
        $table->addUniqueIndex(['slug_id']);
    }

    /**
     * Add oro_cms_page foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCmsPageForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_cms_page');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_redirect_slug'),
            ['current_slug_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
//@TODO will be handled in BB-4943
//        $table->addForeignKeyConstraint(
//            $schema->getTable('oro_cms_page'),
//            ['parent_id'],
//            ['id'],
//            ['onDelete' => 'CASCADE', 'onUpdate' => null]
//        );
    }

    /**
     * Add oro_cms_page_to_slug foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCmsPageToSlugForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_cms_page_to_slug');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_cms_page'),
            ['page_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_redirect_slug'),
            ['slug_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Create oro_cms_login_page table
     *
     * @param Schema $schema
     */
    protected function createOroCmsLoginPageTable(Schema $schema)
    {
        $table = $schema->createTable(self::CMS_LOGIN_PAGE_TABLE);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('top_content', 'text', ['notnull' => false]);
        $table->addColumn('bottom_content', 'text', ['notnull' => false]);
        $table->addColumn('css', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * @param Schema $schema
     */
    protected function addImageAssociations(Schema $schema)
    {
        $this->attachmentExtension->addImageRelation(
            $schema,
            self::CMS_LOGIN_PAGE_TABLE,
            'logoImage',
            [],
            self::MAX_LOGO_IMAGE_SIZE_IN_MB
        );

        $this->attachmentExtension->addImageRelation(
            $schema,
            self::CMS_LOGIN_PAGE_TABLE,
            'backgroundImage',
            [],
            self::MAX_BACKGROUND_IMAGE_SIZE_IN_MB
        );
    }

    /**
     * @param Schema $schema
     */
    public function addWebCatalogPageTypes(Schema $schema)
    {
        if ($schema->hasTable('oro_web_catalog_variant')) {
            $table = $schema->getTable('oro_web_catalog_variant');

            $this->extendExtension->addManyToOneRelation(
                $schema,
                $table,
                'landing_page_cms_page',
                'oro_cms_page',
                'id',
                [
                    ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_READONLY,
                    'entity' => ['label' => 'oro.cms.page.entity_label'],
                    'extend' => [
                        'is_extend' => true,
                        'owner' => ExtendScope::OWNER_CUSTOM
                    ],
                    'dataaudit' => ['auditable' => true]
                ]
            );
        }
    }
}
