<?php

namespace Oro\Bundle\PricingBundle\Migrations\Schema\v1_10;

use Doctrine\DBAL\Schema\Schema;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityExtendConfigMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class UpdatePriceListExtendEntity implements
    Migration,
    ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @inheritDoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $commandExecutor = $this->container->get('oro_entity_config.tools.command_executor');
        $queries->addPostQuery(
            new UpdateEntityExtendConfigMigrationQuery($commandExecutor)
        );
    }
}
