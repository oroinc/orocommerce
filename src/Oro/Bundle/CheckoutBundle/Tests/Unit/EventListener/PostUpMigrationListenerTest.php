<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Oro\Bundle\CheckoutBundle\EventListener\PostUpMigrationListener;
use Oro\Bundle\CheckoutBundle\Migrations\Schema\RemoveWorkflowFieldsMigration;
use Oro\Bundle\MigrationBundle\Event\PostMigrationEvent;

class PostUpMigrationListenerTest extends \PHPUnit\Framework\TestCase
{
    public function testOnPostUp()
    {
        $event = $this->createMock(PostMigrationEvent::class);
        $event->expects($this->once())
            ->method('addMigration')
            ->with($this->isInstanceOf(RemoveWorkflowFieldsMigration::class), true);

        $listener = new PostUpMigrationListener();
        $listener->onPostUp($event);
    }
}
