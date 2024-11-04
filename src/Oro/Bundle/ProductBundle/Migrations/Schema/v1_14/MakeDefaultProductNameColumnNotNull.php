<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_14;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class MakeDefaultProductNameColumnNotNull implements
    Migration,
    OrderedMigrationInterface
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_product');
        $table->getColumn('name')->setNotnull(true);
        $table->getColumn('name_uppercase')->setNotnull(true);
    }

    #[\Override]
    public function getOrder()
    {
        return 30;
    }
}
