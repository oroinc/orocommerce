<?php

namespace Oro\Bundle\CommerceMenuBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtension;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroCommerceMenuBundleInstaller implements
    Installation,
    AttachmentExtensionAwareInterface
{
    const ORO_COMMERCE_MENU_UPDATE_TABLE_NAME = 'oro_commerce_menu_upd';
    const ORO_COMMERCE_MENU_UPDATE_TITLE_TABLE_NAME = 'oro_commerce_menu_upd_title';
    
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
        return 'v1_1';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroCommerceMenuUpdateTable($schema);
        $this->createOroCommerceMenuUpdateTitleTable($schema);

        /** Foreign keys generation **/
        $this->addOroCommerceMenuUpdateTitleForeignKeys($schema);

        /** Associations */
        $this->addOroCommerceMenuUpdateImageAssociation($schema);
    }

    /**
     * Create oro_commerce_menu_upd table.
     *
     * @param Schema $schema
     */
    protected function createOroCommerceMenuUpdateTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_COMMERCE_MENU_UPDATE_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('key', 'string', ['length' => 100]);
        $table->addColumn('parent_key', 'string', ['length' => 100, 'notnull' => false]);
        $table->addColumn('uri', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('menu', 'string', ['length' => 100]);
        $table->addColumn('ownership_type', 'string', []);
        $table->addColumn('owner_id', 'integer', ['notnull' => true]);
        $table->addColumn('is_active', 'boolean', []);
        $table->addColumn('is_divider', 'boolean', []);
        $table->addColumn('is_custom', 'boolean', []);
        $table->addColumn('priority', 'integer', ['notnull' => false]);
        $table->addColumn('condition', 'string', ['length' => 512, 'notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['key', 'ownership_type'], 'unq_qroup');
    }

    /**
     * Create oro_commerce_menu_upd_title table
     *
     * @param Schema $schema
     */
    protected function createOroCommerceMenuUpdateTitleTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_COMMERCE_MENU_UPDATE_TITLE_TABLE_NAME);
        $table->addColumn('menu_update_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['menu_update_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id']);
    }

    /**
     * Add oro_commerce_menu_upd_title foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCommerceMenuUpdateTitleForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_COMMERCE_MENU_UPDATE_TITLE_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_COMMERCE_MENU_UPDATE_TABLE_NAME),
            ['menu_update_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * @param Schema $schema
     */
    public function addOroCommerceMenuUpdateImageAssociation(Schema $schema)
    {
        $this->attachmentExtension->addImageRelation(
            $schema,
            self::ORO_COMMERCE_MENU_UPDATE_TABLE_NAME,
            'image',
            [],
            self::MAX_MENU_UPDATE_IMAGE_SIZE_IN_MB,
            self::THUMBNAIL_WIDTH_SIZE_IN_PX,
            self::THUMBNAIL_HEIGHT_SIZE_IN_PX
        );
    }
}
