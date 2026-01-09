<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Oro\Bundle\CheckoutBundle\Migrations\Schema\RemoveWorkflowFieldsMigration;
use Oro\Bundle\MigrationBundle\Event\PostMigrationEvent;

/**
 * Handles post-migration events to remove legacy workflow fields.
 *
 * Listens to post-migration events and schedules the removal of legacy workflow fields
 * from the checkout entity schema.
 */
class PostUpMigrationListener
{
    public function onPostUp(PostMigrationEvent $event)
    {
        $event->addMigration(new RemoveWorkflowFieldsMigration(), true);
    }
}
