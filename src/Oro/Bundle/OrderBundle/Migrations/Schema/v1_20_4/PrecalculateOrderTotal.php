<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v1_20_4;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class PrecalculateOrderTotal implements Migration, ContainerAwareInterface
{
    use ContainerAwareTrait;

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $queries->addQuery(new PrecalculateOrderTotalMigrationQuery(
            $this->container->get('oro_message_queue.message_producer')
        ));
    }
}
