<?php

namespace OroB2B\Bundle\CheckoutBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BCheckoutBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addCheckoutTypeColumn($schema);
        $this->setTypeExistingCheckouts($queries);
    }

    /**
     * Add checkout type column
     *
     * @param Schema $schema
     */
    protected function addCheckoutTypeColumn(Schema $schema)
    {
        $table = $schema->getTable('orob2b_checkout');
        $table->addColumn('type', 'string', ['notnull' => true, 'length' => 30]);
    }

    /**
     * Set type existing checkouts
     *
     * @param QueryBag $queries
     */
    protected function setTypeExistingCheckouts(QueryBag $queries)
    {
        $sql = "UPDATE orob2b_checkout SET type='checkout'";
        $queries->addQuery($sql);
    }
}
