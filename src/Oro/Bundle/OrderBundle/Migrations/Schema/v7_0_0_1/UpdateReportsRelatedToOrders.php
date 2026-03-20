<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v7_0_0_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Updates enum filter values in report definitions to use the new format with enum code prefix.
 */
class UpdateReportsRelatedToOrders implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $queries->addQuery(new UpdateOrderLineItemRelationQuery());
    }
}
