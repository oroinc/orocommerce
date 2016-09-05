<?php

namespace Oro\Bundle\MenuBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtension;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class OroMenuBundleInstaller implements Installation, AttachmentExtensionAwareInterface
{
    const MAX_MENU_ITEM_IMAGE_SIZE_IN_MB = 1;

    /**
     * @var AttachmentExtension
     */
    protected $attachmentExtension;

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
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroMenuItemTable($schema);
        $this->createOroMenuItemTitleTable($schema);

        /** Foreign keys generation **/
        $this->addOroMenuItemForeignKeys($schema);
        $this->addOroMenuItemTitleForeignKeys($schema);
    }

    /**
     * Create oro_menu_item table
     *
     * @param Schema $schema
     */
    protected function createOroMenuItemTable(Schema $schema)
    {
        $table = $schema->createTable('oro_menu_item');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('parent_id', 'integer', ['notnull' => false]);
        $table->addColumn('uri', 'text', ['notnull' => false]);
        $table->addColumn('display', 'boolean', []);
        $table->addColumn('display_children', 'boolean', []);
        $table->addColumn('tree_left', 'integer', []);
        $table->addColumn('tree_level', 'integer', []);
        $table->addColumn('tree_right', 'integer', []);
        $table->addColumn('tree_root', 'integer', ['notnull' => false]);
        $table->addColumn('data', 'array', ['comment' => '(DC2Type:array)']);
        $table->setPrimaryKey(['id']);
        $this->attachmentExtension->addImageRelation(
            $schema,
            'oro_menu_item',
            'image',
            [],
            self::MAX_MENU_ITEM_IMAGE_SIZE_IN_MB
        );
    }

    /**
     * Create oro_menu_item_title table
     *
     * @param Schema $schema
     */
    protected function createOroMenuItemTitleTable(Schema $schema)
    {
        $table = $schema->createTable('oro_menu_item_title');
        $table->addColumn('menu_item_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['menu_item_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id'], 'UNIQ_D67C4C5FEB576E89');
    }

    /**
     * Add oro_menu_item foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroMenuItemForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_menu_item');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_menu_item'),
            ['parent_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_menu_item_title foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroMenuItemTitleForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_menu_item_title');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_menu_item'),
            ['menu_item_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
