<?php

namespace Oro\Bundle\UPSBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AddTestModeToUPSTransport implements Migration, ContainerAwareInterface, OrderedMigrationInterface
{
    const PRODUCTION_URL_PARAMETER = 'oro_ups.api.url.production';

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 10;
    }

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_integration_transport');
        $table->addColumn('ups_test_mode', 'boolean', ['notnull' => false, 'default' => false]);

        $productionUrl = $this->container->getParameter(self::PRODUCTION_URL_PARAMETER);

        $queries->addPostQuery(
            new MigrateBaseUrlToTestModeQuery($productionUrl)
        );
    }
}
