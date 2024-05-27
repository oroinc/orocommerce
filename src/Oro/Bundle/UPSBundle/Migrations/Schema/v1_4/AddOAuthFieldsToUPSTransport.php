<?php

namespace Oro\Bundle\UPSBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddOAuthFieldsToUPSTransport implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_integration_transport');

        if (!$table->hasColumn('ups_client_id')) {
            $table->addColumn('ups_client_id', 'string', ['notnull' => false, 'length' => 255]);
        }
        if (!$table->hasColumn('ups_client_secret')) {
            $table->addColumn('ups_client_secret', 'string', ['notnull' => false, 'length' => 255]);
        }
        if (!$table->hasColumn('ups_access_token')) {
            $table->addColumn('ups_access_token', 'text', ['notnull' => false]);
        }
        if (!$table->hasColumn('ups_access_token_expires')) {
            $table->addColumn(
                'ups_access_token_expires',
                'datetime',
                ['notnull' => false, 'comment' => '(DC2Type:datetime)']
            );
        }
    }
}
