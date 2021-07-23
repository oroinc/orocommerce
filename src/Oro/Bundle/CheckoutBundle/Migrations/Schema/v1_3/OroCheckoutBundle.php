<?php

namespace Oro\Bundle\CheckoutBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroCheckoutBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->updateCheckoutTable($schema);
        $this->updateCheckoutSourceTable($schema);
    }

    protected function updateCheckoutTable(Schema $schema)
    {
        $table = $schema->getTable('oro_checkout');
        $table->addColumn('deleted', 'boolean', ['default' => false]);
    }

    protected function updateCheckoutSourceTable(Schema $schema)
    {
        $table = $schema->getTable('oro_checkout_source');
        $table->addColumn('deleted', 'boolean', ['default' => false]);
    }
}
