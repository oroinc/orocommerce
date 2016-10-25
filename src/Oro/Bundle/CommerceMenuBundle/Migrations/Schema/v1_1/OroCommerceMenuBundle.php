<?php

namespace Oro\Bundle\CommerceMenuBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\StringType;

use Oro\Bundle\CommerceMenuBundle\Migrations\Schema\OroCommerceMenuBundleInstaller;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroCommerceMenuBundle implements Migration, RenameExtensionAwareInterface
{
    /**
     * @var RenameExtension
     */
    private $renameExtension;

    /**
     * {@inheritdoc}
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Table updates **/
        $this->updateOroCommerceMenuUpdateTable($schema);
        $this->renameOroCommerceMenuUpdateAndMenuUpdateTitleTables($schema, $queries);
        $this->createOroCommerceMenuUpdateDescriptionTable($schema);
        $this->addOroCommerceMenuUpdateDescriptionForeignKeys($schema);
    }

    /**
     * Update oro_commerce_menu_upd
     *
     * @param Schema $schema
     */
    protected function updateOroCommerceMenuUpdateTable(Schema $schema)
    {
        $table = $schema->getTable('oro_front_nav_menu_upd');
        $table->addColumn('icon', 'string', ['length' => 150, 'notnull' => false]);
        $table->addColumn('is_divider', 'boolean', []);
        $table->addColumn('is_custom', 'boolean', []);
        $table->changeColumn('ownership_type', ['type' => StringType::getType('string')]);
        $table->changeColumn('owner_id', ['notnull' => true]);
        $table->changeColumn('uri', ['length' => 1023]);
        $table->removeForeignKey('FK_1B58D24F18F45C82');
        $table->dropColumn('website_id');
        $table->addUniqueIndex(['key', 'ownership_type', 'owner_id'], 'oro_commerce_menu_upd_uidx');
    }

    /**
     * Create `oro_navigation_menu_upd_descr` table
     *
     * @param Schema $schema
     */
    protected function createOroCommerceMenuUpdateDescriptionTable(Schema $schema)
    {
        $table = $schema->createTable(OroCommerceMenuBundleInstaller::ORO_COMMERCE_MENU_UPDATE_DESCRIPTION_TABLE_NAME);
        $table->addColumn('menu_update_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['menu_update_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id']);
    }

    /**
     * Add `oro_navigation_menu_upd_descr` foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCommerceMenuUpdateDescriptionForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(OroCommerceMenuBundleInstaller::ORO_COMMERCE_MENU_UPDATE_DESCRIPTION_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(OroCommerceMenuBundleInstaller::ORO_COMMERCE_MENU_UPDATE_TABLE_NAME),
            ['menu_update_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Rename oro_front_nav_menu_upd and oro_commerce_menu_upd_title tables
     *
     * @param Schema $schema
     * @param QueryBag $queries
     */
    public function renameOroCommerceMenuUpdateAndMenuUpdateTitleTables($schema, $queries)
    {
        $this->renameExtension->renameTable(
            $schema,
            $queries,
            'oro_front_nav_menu_upd',
            'oro_commerce_menu_upd'
        );

        $this->renameExtension->renameTable(
            $schema,
            $queries,
            'oro_front_nav_menu_upd_title',
            'oro_commerce_menu_upd_title'
        );
    }
}
