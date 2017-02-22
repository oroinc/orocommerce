<?php

namespace Oro\Bundle\UPSBundle\Migrations\Schema\v1_3;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RemoveBaseUrlFromUPSTransport implements Migration, DependentFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return [
            AddTestModeToUPSTransport::class
        ];
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
