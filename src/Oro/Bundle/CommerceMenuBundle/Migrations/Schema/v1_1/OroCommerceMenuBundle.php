<?php

namespace Oro\Bundle\CommerceMenuBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\StringType;

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
