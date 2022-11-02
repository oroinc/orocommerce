<?php

namespace Oro\Bundle\CheckoutBundle\Migrations\Schema\v1_9;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Adds new column for registered customer user to the oro_checkout table
 */
class OroCheckoutBundle implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addRegisteredCustomerUser($schema);
    }

    private function addRegisteredCustomerUser(Schema $schema)
    {
        $table = $schema->getTable('oro_checkout');
        $table->addColumn('registered_customer_user_id', 'integer', ['notnull' => false]);
        $table->addUniqueIndex(['registered_customer_user_id'], 'UNIQ_C040FD5916A5A0D');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_user'),
            ['registered_customer_user_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }
}
