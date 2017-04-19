<?php

namespace Oro\Bundle\UPSBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RemoveBaseUrlFromUPSTransport implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 20;
    }

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_integration_transport');
        $table->dropColumn('ups_base_url');
    }
}
