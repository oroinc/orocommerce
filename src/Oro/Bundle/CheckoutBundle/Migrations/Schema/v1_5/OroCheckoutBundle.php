<?php

namespace Oro\Bundle\CheckoutBundle\Migrations\Schema\v1_5;

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
    }

    protected function updateCheckoutTable(Schema $schema)
    {
        $table = $schema->getTable('oro_checkout');
        $table->addColumn('completed', 'boolean', ['default' => false]);
        $table->addColumn('completed_data', 'json_array');
    }
}
