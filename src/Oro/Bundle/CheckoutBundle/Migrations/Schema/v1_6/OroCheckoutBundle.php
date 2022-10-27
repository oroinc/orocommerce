<?php

namespace Oro\Bundle\CheckoutBundle\Migrations\Schema\v1_6;

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
        if ($table->hasColumn('serialized_data')) {
            $table->dropColumn('serialized_data');
        }
    }
}
