<?php

namespace Oro\Bundle\UPSBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class OroUPSBundle implements Migration, ContainerAwareInterface
{
    use ContainerAwareTrait;

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $queries->addQuery(
            new UpdatePasswordMigrationQuery($this->container)
        );
    }
}
