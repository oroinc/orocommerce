<?php

namespace Oro\Bundle\SaleBundle\Migrations\Schema\v7_1_0_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigEntityValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\SaleBundle\Entity\Quote;

/**
 * Enables webhook access for the Quote entity on existing installations.
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
            Quote::class,
            'integration',
            'webhook_accessible',
            true,
            false
        ));
    }
}
