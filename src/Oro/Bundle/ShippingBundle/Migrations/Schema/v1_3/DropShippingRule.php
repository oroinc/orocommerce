<?php

namespace Oro\Bundle\ShippingBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class DropShippingRule implements Migration, OrderedMigrationInterface
{
    /**
     * @return int
     */
    public function getOrder()
    {
        return 4;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->dropOroShippingRuleTable($schema);
    }

    /**
     * @param Schema $schema
     */
    private function dropOroShippingRuleTable(Schema $schema)
    {
        $schema->dropTable('oro_shipping_rule');
    }
}
