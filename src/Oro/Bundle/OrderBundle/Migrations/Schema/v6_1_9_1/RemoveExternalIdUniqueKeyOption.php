<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v6_1_9_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RemoveExternalIdUniqueKeyOption implements Migration
{
    public function up(Schema $schema, QueryBag $queries): void
    {
        if (!$schema->hasTable('oro_order')) {
            return;
        }

        if (!$schema->getTable('oro_order')->hasColumn('external_id')) {
            return;
        }

        $queries->addPostQuery(new RemoveExternalIdFromOrderUniqueKeyQuery());
    }
}
