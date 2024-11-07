<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v1_23;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class UpdateImmutableOrderInternalStatuses implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $queries->addQuery(new UpdateImmutableOrderInternalStatusesQuery());
    }
}
