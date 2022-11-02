<?php

namespace Oro\Bundle\ShippingBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class CreateForeignKeysWithNewTable implements Migration, OrderedMigrationInterface
{
    /**
     * @internal
     */
    const SHIPPING_RULE_CLASS = 'Oro\Bundle\ShippingBundle\Entity\ShippingRule';

    /**
     * @return int
     */
    public function getOrder()
    {
        return 50;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addOroShipMethodConfigForeignKeys($schema);
        $this->addOroShippingRuleDestinationForeignKeys($schema);
    }

    private function addOroShipMethodConfigForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_ship_method_config');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_ship_method_configs_rule'),
            ['rule_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    private function addOroShippingRuleDestinationForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_shipping_rule_destination');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_ship_method_configs_rule'),
            ['rule_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}
