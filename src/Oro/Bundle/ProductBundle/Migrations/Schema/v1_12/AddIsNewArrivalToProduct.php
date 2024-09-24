<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_12;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddIsNewArrivalToProduct implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $schema->getTable('oro_product')
            ->addColumn('is_new_arrival', 'boolean', ['default' => false]);
    }
}
