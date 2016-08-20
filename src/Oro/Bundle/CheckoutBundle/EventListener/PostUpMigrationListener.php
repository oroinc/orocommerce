<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Oro\Bundle\MigrationBundle\Event\PostMigrationEvent;
use Oro\Bundle\CheckoutBundle\Migrations\Schema\RemoveWorkflowFieldsMigration;

/**
 * TODO: remove this listener after stable release
 */
class PostUpMigrationListener
{
    /**
     * @param PostMigrationEvent $event
     */
    public function onPostUp(PostMigrationEvent $event)
    {
        $event->addMigration(new RemoveWorkflowFieldsMigration(), true);
    }
}
