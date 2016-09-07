<?php

namespace Oro\Bundle\FrontendNavigationBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtension;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroFrontendNavigationBundleInstaller implements
    Installation,
    AttachmentExtensionAwareInterface
{
    const MAX_MENU_UPDATE_IMAGE_SIZE_IN_MB = 10;
    const THUMBNAIL_WIDTH_SIZE_IN_PX = 100;
    const THUMBNAIL_HEIGHT_SIZE_IN_PX = 100;

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
        $this->createOroFrontendNavigationMenuUpdateTable($schema);
        $this->createOroFrontendNavigationMenuUpdateTitleTable($schema);

        /** Foreign keys generation **/
        $this->addOroFrontendNavigationMenuUpdateForeignKeys($schema);
        $this->addOroFrontendNavigationMenuUpdateTitleForeignKeys($schema);

        /** Associations */
        $this->addOroFrontendNavigationMenuUpdateImageAssociation($schema);
    }

    /**
     * Create oro_front_nav_menu_update table.
     *
     * @param Schema $schema
     */
    protected function createOroFrontendNavigationMenuUpdateTable(Schema $schema)
    {
        $table = $schema->createTable('oro_front_nav_menu_update');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('key', 'string', ['length' => 100]);
        $table->addColumn('parent_key', 'string', ['length' => 100, 'notnull' => false]);
        $table->addColumn('uri', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('menu', 'string', ['length' => 100]);
        $table->addColumn('ownership_type', 'integer', []);
        $table->addColumn('owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('is_active', 'boolean', []);
        $table->addColumn('priority', 'integer', ['notnull' => false]);
        $table->addColumn('condition', 'string', ['length' => 512, 'notnull' => false]);
        $table->addColumn('website_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_front_nav_menu_upd_title table
     *
     * @param Schema $schema
     */
    protected function createOroFrontendNavigationMenuUpdateTitleTable(Schema $schema)
    {
        $table = $schema->createTable('oro_front_nav_menu_upd_title');
        $table->addColumn('menu_update_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['menu_update_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id']);
    }

    /**
     * Add oro_front_nav_menu_update foreign keys
     *
     * @param Schema $schema
     */
    protected function addOroFrontendNavigationMenuUpdateForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_front_nav_menu_update');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_website'),
            ['website_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_front_nav_menu_upd_title foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroFrontendNavigationMenuUpdateTitleForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_front_nav_menu_upd_title');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_front_nav_menu_update'),
            ['menu_update_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * @param Schema $schema
     */
    public function addOroFrontendNavigationMenuUpdateImageAssociation(Schema $schema)
    {
        $this->attachmentExtension->addImageRelation(
            $schema,
            'oro_front_nav_menu_update',
            'image',
            [],
            self::MAX_MENU_UPDATE_IMAGE_SIZE_IN_MB,
            self::THUMBNAIL_WIDTH_SIZE_IN_PX,
            self::THUMBNAIL_HEIGHT_SIZE_IN_PX
        );
    }
}
