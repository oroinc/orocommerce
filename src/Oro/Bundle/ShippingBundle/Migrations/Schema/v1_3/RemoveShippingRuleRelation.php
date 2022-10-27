<?php

namespace Oro\Bundle\ShippingBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\MigrationConstraintTrait;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RemoveShippingRuleRelation implements Migration, OrderedMigrationInterface
{
    use MigrationConstraintTrait;

    /**
     * @return int
     */
    public function getOrder()
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->modifyOroShippingRuleDestinationTable($schema);
        $this->modifyOroShippingRuleMethodConfigTable($schema);
    }

    private function modifyOroShippingRuleDestinationTable(Schema $schema)
    {
        $table = $schema->getTable('oro_shipping_rule_destination');
        $table->removeForeignKey($this->getConstraintName($table, 'rule_id'));
    }

    private function modifyOroShippingRuleMethodConfigTable(Schema $schema)
    {
        $table = $schema->getTable('oro_shipping_rule_mthd_config');
        $table->removeForeignKey($this->getConstraintName($table, 'rule_id'));
    }
}
