<?php

namespace Oro\Bundle\UPSBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;

class AddTestModeToUPSTransport implements Migration, ContainerAwareInterface, OrderedMigrationInterface
{
    use ContainerAwareTrait;

    #[\Override]
    public function getOrder(): int
    {
        return 10;
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_integration_transport');
        $table->addColumn('ups_test_mode', 'boolean', ['notnull' => false, 'default' => false]);

        $queries->addPostQuery(
            new MigrateBaseUrlToTestModeQuery($this->container->getParameter('oro_ups.api.url.production'))
        );
    }
}
