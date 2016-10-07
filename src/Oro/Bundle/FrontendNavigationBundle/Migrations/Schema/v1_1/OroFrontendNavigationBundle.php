<?php

namespace Oro\Bundle\FrontendNavigationBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\StringType;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroFrontendNavigationBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Table updates **/
        $this->updateOroNavigationMenuUpdateTable($schema);
    }

    /**
     * Update oro_navigation_menu_upd
     *
     * @param Schema $schema
     */
    protected function updateOroNavigationMenuUpdateTable(Schema $schema)
    {
        $table = $schema->getTable('oro_front_nav_menu_upd');
        $table->addColumn('is_divider', 'boolean', []);
        $table->addColumn('is_custom', 'boolean', []);
        $table->changeColumn('ownership_type', ['type' => StringType::getType('string')]);
        $table->dropColumn('website_id');
    }
}
