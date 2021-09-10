<?php

namespace Oro\Bundle\ShippingBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class ModifyOldTables implements Migration, OrderedMigrationInterface
{
    /**
     * @return int
     */
    public function getOrder()
    {
        return 30;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->modifyOroShippingRuleDestinationTable($schema);
    }

    private function modifyOroShippingRuleDestinationTable(Schema $schema)
    {
        $table = $schema->getTable('oro_shipping_rule_destination');
        $table->dropColumn('postal_code');
    }
}
