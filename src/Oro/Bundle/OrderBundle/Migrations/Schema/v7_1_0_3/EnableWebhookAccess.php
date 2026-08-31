<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v7_1_0_3;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigEntityValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\OrderBundle\Entity\Order;

/**
 * Enables webhook access for the Order entity on existing installations.
 *
 * The value is updated only where webhooks are currently disabled, so an entity an administrator
 * has already enabled is left untouched.
 */
class EnableWebhookAccess implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $queries->addPostQuery(new UpdateEntityConfigEntityValueQuery(
            Order::class,
            'integration',
            'webhook_accessible',
            true,
            false
        ));
    }
}
