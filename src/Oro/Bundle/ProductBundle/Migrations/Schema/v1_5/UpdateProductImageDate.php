<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_5;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class UpdateProductImageDate implements Migration, OrderedMigrationInterface
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        // fix updated_at for product image
        $queries->addPreQuery(
            new ParametrizedSqlMigrationQuery(
                'UPDATE oro_product_image SET updated_at = ? WHERE updated_at IS NULL',
                [new \DateTime('now', new \DateTimeZone('UTC'))],
                [Types::DATETIME_MUTABLE]
            )
        );
    }

    #[\Override]
    public function getOrder()
    {
        return 20;
    }
}
