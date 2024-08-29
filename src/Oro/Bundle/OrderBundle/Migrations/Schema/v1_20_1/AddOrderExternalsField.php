<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v1_20_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddOrderExternalsField implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_order');
        if (!$table->hasColumn('is_external')) {
            $table->addColumn('is_external', 'boolean', ['default' => false]);
        }
    }
}
