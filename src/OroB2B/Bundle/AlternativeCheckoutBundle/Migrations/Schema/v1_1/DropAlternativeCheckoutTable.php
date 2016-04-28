<?php

namespace OroB2B\Bundle\CheckoutBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class DropAlternativeCheckoutTable implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 20;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->dropAlternativeCheckoutTable($schema);
    }

     /**
     * Drop alternative checkout table
     *
     * @param Schema $schema
     */
    protected function dropAlternativeCheckoutTable(Schema $schema)
    {
        $schema->dropTable('orob2b_alternative_checkout');
    }
}
