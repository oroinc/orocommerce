<?php

namespace Oro\Bundle\FrontendNavigationBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroFrontendNavigationBundleInstaller implements Installation
{
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

        /** Foreign keys generation **/
        $this->addOroFrontendNavigationMenuUpdateForeignKeys($schema);
    }

    /**
     * Create orob2b_front_nav_menu_update table.
     *
     * @param Schema $schema
     */
    protected function createOroFrontendNavigationMenuUpdateTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_front_nav_menu_update');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('key', 'string', ['length' => 100]);
        $table->addColumn('parent_id', 'string', ['length' => 100]);
        $table->addColumn('title', 'string', ['length' => 255]);
        $table->addColumn('menu', 'string', ['length' => 100]);
        $table->addColumn('ownership_type', 'integer', []);
        $table->addColumn('owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('is_active', 'boolean', []);
        $table->addColumn('priority', 'integer', []);
        $table->addColumn('image', 'string', ['length' => 256]);
        $table->addColumn('description', 'string', ['length' => 100]);
        $table->addColumn('condition', 'string', ['length' => 512]);
        $table->addColumn('website_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add orob2b_front_nav_menu_update freign keys.
     *
     * @param Schema $schema
     */
    protected function addOroFrontendNavigationMenuUpdateForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_front_nav_menu_update');

        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_website'),
            ['website_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}
