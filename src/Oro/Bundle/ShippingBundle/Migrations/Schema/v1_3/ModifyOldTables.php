<?php

namespace Oro\Bundle\ShippingBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\MigrationConstraintTrait;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class ModifyOldTables implements Migration, OrderedMigrationInterface
{
    use MigrationConstraintTrait;

    /**
     * @return int
     */
    public function getOrder()
    {
        return 3;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->modifyOroShippingRuleDestinationTable($schema);
        $this->modifyOroShippingRuleMethodConfigTable($schema);
    }

    /**
     * @param Schema $schema
     */
    private function modifyOroShippingRuleDestinationTable(Schema $schema)
    {
        $table = $schema->getTable('oro_shipping_rule_destination');
        $table->dropColumn('postal_code');

        $table->removeForeignKey($this->getConstraintName($table, 'rule_id'));
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_ship_method_configs_rule'),
            ['rule_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * @param Schema $schema
     */
    private function modifyOroShippingRuleMethodConfigTable(Schema $schema)
    {
        $table = $schema->getTable('oro_ship_method_config');

        $table->removeForeignKey($this->getConstraintName($table, 'rule_id'));
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_ship_method_configs_rule'),
            ['rule_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}
