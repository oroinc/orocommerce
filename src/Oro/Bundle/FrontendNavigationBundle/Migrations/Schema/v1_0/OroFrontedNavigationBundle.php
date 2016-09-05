<?php
namespace Oro\Bundle\FrontendNavigationBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroFrontendNavigationBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Generate table oro_front_nav_menu_update **/
        $table = $schema->createTable('oro_front_nav_menu_update');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('key', 'string', ['length' => 100]);
        $table->addColumn('parent_id', 'string', ['length' => 100]);
        $table->addColumn('title', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('uri', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('menu', 'string', ['length' => 100]);
        $table->addColumn('ownership_type', 'integer', []);
        $table->addColumn('owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('is_active', 'boolean', ['notnull' => false]);
        $table->addColumn('priority', 'integer', ['notnull' => false]);
        $table->addColumn('image', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('description', 'string', ['length' => 100, 'notnull' => false]);
        $table->addColumn('condition', 'string', ['length' => 512, 'notnull' => false]);
        $table->addColumn('website_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);

        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_website'),
            ['website_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}
