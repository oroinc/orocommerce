<?php

namespace Oro\Bundle\ShippingBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RemoveMultiShippingChannels implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        // Delete "Multi Shipping" shipping rules and all related data.
        // Data from "oro_ship_method_configs_rule", "oro_ship_method_config" and "oro_ship_method_type_config" tables
        // are deleted automatically because there are "ON DELETE CASCADE" rules for them.
        $queries->addQuery(new ParametrizedSqlMigrationQuery(
            'DELETE FROM oro_rule WHERE EXISTS('
            . 'SELECT 1 FROM oro_ship_method_configs_rule'
            . ' INNER JOIN oro_ship_method_config ON oro_ship_method_config.rule_id = oro_ship_method_configs_rule.id'
            . ' WHERE oro_ship_method_config.method LIKE :method'
            . ' AND oro_rule.id = oro_ship_method_configs_rule.rule_id)',
            ['method' => 'multi_shipping_%']
        ));
        // Delete "Multi Shipping" integration channels and all related data.
        // Data from "oro_integration_channel_status" and "oro_integration_transport" tables
        // need to be deleted manually because there are no "ON DELETE CASCADE" rules for them.
        $queries->addQuery(new ParametrizedSqlMigrationQuery(
            'DELETE FROM oro_integration_channel_status WHERE EXISTS('
            . 'SELECT 1 FROM oro_integration_channel WHERE'
            . ' oro_integration_channel.type = :channel_type'
            . ' AND oro_integration_channel_status.channel_id = oro_integration_channel.id)',
            ['channel_type' => 'multi_shipping']
        ));
        $queries->addQuery(new ParametrizedSqlMigrationQuery(
            'DELETE FROM oro_integration_channel WHERE type = :channel_type',
            ['channel_type' => 'multi_shipping']
        ));
        $queries->addQuery(new ParametrizedSqlMigrationQuery(
            'DELETE FROM oro_integration_transport WHERE type = :transport_type',
            ['transport_type' => 'multishippingsettings']
        ));
    }
}
