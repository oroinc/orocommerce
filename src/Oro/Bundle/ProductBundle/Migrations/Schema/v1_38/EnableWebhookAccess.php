<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_38;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigEntityValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Enables webhook access for the Product entity on existing installations.
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
            Product::class,
            'integration',
            'webhook_accessible',
            true,
            false
        ));
    }
}
