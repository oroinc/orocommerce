<?php

namespace OroB2B\Bundle\CMSBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtension;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BCMSBundleInstaller implements Installation, AttachmentExtensionAwareInterface
{
    const CMS_LOGIN_PAGE_TABLE = 'orob2b_cms_login_page';
    const MAX_LOGO_IMAGE_SIZE_IN_MB = 10;
    const MAX_BACKGROUND_IMAGE_SIZE_IN_MB = 10;

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_0';
    }

    /** @var AttachmentExtension */
    protected $attachmentExtension;

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
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOrob2BCmsPageTable($schema);
        $this->createOrob2BCmsPageToSlugTable($schema);
        $this->createOroB2BCmsLoginPageTable($schema);

        /** Foreign keys generation **/
        $this->addOrob2BCmsPageForeignKeys($schema);
        $this->addOrob2BCmsPageToSlugForeignKeys($schema);

        $this->addImageAssociations($schema);
    }

    /**
     * Create orob2b_cms_page table
     *
     * @param Schema $schema
     */
    protected function createOrob2BCmsPageTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_cms_page');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('current_slug_id', 'integer', ['notnull' => false]);
        $table->addColumn('parent_id', 'integer', ['notnull' => false]);
        $table->addColumn('title', 'string', ['length' => 255]);
        $table->addColumn('content', 'text', []);
        $table->addColumn('tree_left', 'integer', []);
        $table->addColumn('tree_level', 'integer', []);
        $table->addColumn('tree_right', 'integer', []);
        $table->addColumn('tree_root', 'integer', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->addUniqueIndex(['current_slug_id']);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create orob2b_cms_page_to_slug table
     *
     * @param Schema $schema
     */
    protected function createOrob2BCmsPageToSlugTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_cms_page_to_slug');
        $table->addColumn('page_id', 'integer', []);
        $table->addColumn('slug_id', 'integer', []);
        $table->setPrimaryKey(['page_id', 'slug_id']);
        $table->addUniqueIndex(['slug_id']);
    }

    /**
     * Add orob2b_cms_page foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BCmsPageForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_cms_page');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_redirect_slug'),
            ['current_slug_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_cms_page'),
            ['parent_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_cms_page_to_slug foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BCmsPageToSlugForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_cms_page_to_slug');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_cms_page'),
            ['page_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_redirect_slug'),
            ['slug_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Create orob2b_cms_login_page table
     *
     * @param Schema $schema
     */
    protected function createOroB2BCmsLoginPageTable(Schema $schema)
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
}
