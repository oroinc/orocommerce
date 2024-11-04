<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_15;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class MakeDefaultBrandTitleNotNull implements
    Migration,
    OrderedMigrationInterface
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_brand');
        $table->getColumn('default_title')->setNotnull(true);
    }

    #[\Override]
    public function getOrder()
    {
        return 30;
    }
}
