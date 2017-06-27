<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_12;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddIsNewArrivalToProduct implements Migration
{
    /**
     * @internal
     */
    const PRODUCT_TABLE_NAME = 'oro_product';

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable(self::PRODUCT_TABLE_NAME);
        $table->addColumn('is_new_arrival', 'boolean', ['default' => false]);
    }
}
