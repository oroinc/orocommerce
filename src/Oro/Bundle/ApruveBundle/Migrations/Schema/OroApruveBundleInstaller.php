<?php

namespace Oro\Bundle\Apruve\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroApruveBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_0';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->updateOroIntegrationTransportTable($schema);
    }

    /**
     * @param Schema $schema
     */
    public function updateOroIntegrationTransportTable(Schema $schema)
    {
        $table = $schema->getTable('oro_integration_transport');
        $table->addColumn('apruve_merchant_id', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('apruve_api_key', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('apruve_learn_more', 'string', ['notnull' => false, 'length' => 1023]);
        $table->addColumn('apruve_webhook_token', 'string', ['notnull' => false, 'length' => 36]);
    }
}
