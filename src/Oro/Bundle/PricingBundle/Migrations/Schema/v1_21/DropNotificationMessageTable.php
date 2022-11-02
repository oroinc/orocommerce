<?php

namespace Oro\Bundle\PricingBundle\Migrations\Schema\v1_21;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Drop `notification_message` table due to messages implementation replacement with NotificationAlerts
 */
class DropNotificationMessageTable implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        if ($schema->hasTable('oro_notification_message')) {
            $schema->dropTable('oro_notification_message');
        }
    }
}
