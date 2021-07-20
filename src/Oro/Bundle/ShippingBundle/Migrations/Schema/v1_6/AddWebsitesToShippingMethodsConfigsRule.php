<?php

namespace Oro\Bundle\ShippingBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddWebsitesToShippingMethodsConfigsRule implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createOroShipMtdsRuleWebsiteTable($schema);
        $this->addOroShipMtdsRuleWebsiteForeignKeys($schema);
    }

    /**
     * Create oro_ship_mtds_rule_website table
     */
    protected function createOroShipMtdsRuleWebsiteTable(Schema $schema)
    {
        $table = $schema->createTable('oro_ship_mtds_rule_website');
        $table->addColumn('oro_ship_mtds_cfgs_rl_id', 'integer', []);
        $table->addColumn('website_id', 'integer', []);
        $table->setPrimaryKey(['oro_ship_mtds_cfgs_rl_id', 'website_id']);
        $table->addIndex(['oro_ship_mtds_cfgs_rl_id'], 'IDX_7EE052E912BB5ED3', []);
    }

    /**
     * Add oro_ship_mtds_rule_website foreign keys.
     */
    protected function addOroShipMtdsRuleWebsiteForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_ship_mtds_rule_website');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_ship_method_configs_rule'),
            ['oro_ship_mtds_cfgs_rl_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_website'),
            ['website_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
