<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v1_18;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\SqlMigrationQuery;

class CreateCaseInsensitiveIndexesForOrder implements Migration, DatabasePlatformAwareInterface
{
    use DatabasePlatformAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        if ($this->platform instanceof PostgreSqlPlatform) {
            $queries->addPostQuery(new SqlMigrationQuery(
                'CREATE INDEX IF NOT EXISTS idx_order_identifier_ci ON oro_order (LOWER(identifier))'
            ));
            $queries->addPostQuery(new SqlMigrationQuery(
                'CREATE INDEX IF NOT EXISTS idx_order_po_number_ci ON oro_order (LOWER(po_number))'
            ));
        }
    }
}
