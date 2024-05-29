<?php

namespace Oro\Bundle\FedexShippingBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class ChangeFedexToRestApi implements Migration
{
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_integration_transport');

        if (!$table->hasColumn('fedex_client_id')) {
            $table->addColumn('fedex_client_id', 'string', ['notnull' => false, 'length' => 255]);
        }
        if (!$table->hasColumn('fedex_client_secret')) {
            $table->addColumn('fedex_client_secret', 'string', ['notnull' => false, 'length' => 255]);
        }
        if (!$table->hasColumn('fedex_pickup_type_rest')) {
            $table->addColumn('fedex_pickup_type_rest', 'string', ['notnull' => false, 'length' => 100]);
        }
        if (!$table->hasColumn('fedex_account_number_rest')) {
            $table->addColumn('fedex_account_number_rest', 'string', ['notnull' => false, 'length' => 100]);
        }
        if (!$table->hasColumn('fedex_access_token')) {
            $table->addColumn('fedex_access_token', 'text', ['notnull' => false]);
        }
        if (!$table->hasColumn('fedex_access_token_expires')) {
            $table->addColumn(
                'fedex_access_token_expires',
                'datetime',
                ['notnull' => false, 'comment' => '(DC2Type:datetime)']
            );
        }
    }
}
