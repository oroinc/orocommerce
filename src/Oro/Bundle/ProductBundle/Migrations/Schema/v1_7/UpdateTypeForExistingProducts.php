<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class UpdateTypeForExistingProducts implements Migration, OrderedMigrationInterface
{
    #[\Override]
    public function getOrder(): int
    {
        return 20;
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        // Add type 'simple' to all existing products
        $queries->addPreQuery(
            new ParametrizedSqlMigrationQuery(
                'UPDATE oro_product SET type = :typeValue WHERE type IS NULL',
                ['typeValue' => 'simple']
            )
        );
    }
}
