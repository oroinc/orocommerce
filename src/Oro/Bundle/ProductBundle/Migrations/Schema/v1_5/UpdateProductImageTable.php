<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_5;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class UpdateProductImageTable implements Migration, OrderedMigrationInterface
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_product_image');
        $table->getColumn('updated_at')->setNotnull(true);
    }

    #[\Override]
    public function getOrder()
    {
        return 30;
    }
}
