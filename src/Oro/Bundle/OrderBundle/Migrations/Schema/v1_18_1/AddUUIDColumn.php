<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v1_18_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Adds a unique identifier used as a reference for the order.
 */
class AddUUIDColumn implements Migration
{
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_order');
        if (!$table->hasColumn('uuid')) {
            $queries->addQuery('ALTER TABLE oro_order ADD COLUMN uuid UUID');
            $queries->addPostQuery('UPDATE oro_order SET uuid = uuid_generate_v4()');
            $queries->addPostQuery('ALTER TABLE oro_order ALTER COLUMN uuid SET NOT NULL');
            $queries->addPostQuery('CREATE INDEX oro_order_uuid ON oro_order (uuid)');
            $queries->addPostQuery('CREATE UNIQUE INDEX UNIQ_388B2E9DD17F50A6 ON oro_order (uuid)');
        }
    }
}
