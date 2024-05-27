<?php

namespace Oro\Bundle\CheckoutBundle\Migrations\Schema\v1_13_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Adds a unique identifier used as a reference for the checkout.
 */
class AddUUIDColumn implements Migration
{
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_checkout');
        if (!$table->hasColumn('uuid')) {
            $queries->addQuery('ALTER TABLE oro_checkout ADD COLUMN uuid UUID');
            $queries->addPostQuery('UPDATE oro_checkout SET uuid = uuid_generate_v4()');
            $queries->addPostQuery('ALTER TABLE oro_checkout ALTER COLUMN uuid SET NOT NULL');
            $queries->addPostQuery('CREATE INDEX oro_checkout_uuid ON oro_checkout (uuid)');
            $queries->addPostQuery('CREATE UNIQUE INDEX UNIQ_C040FD59D17F50A6 ON oro_checkout (uuid)');
        }
    }
}
