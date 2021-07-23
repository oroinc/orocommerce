<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Oro\Bundle\CheckoutBundle\Migrations\Schema\RemoveWorkflowFieldsMigration;
use Oro\Bundle\MigrationBundle\Event\PostMigrationEvent;

/**
 * TODO: remove this listener after stable release
 */
class PostUpMigrationListener
{
    public function onPostUp(PostMigrationEvent $event)
    {
        $event->addMigration(new RemoveWorkflowFieldsMigration(), true);
    }
}
