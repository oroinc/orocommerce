<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Oro\Bundle\CheckoutBundle\EventListener\PostUpMigrationListener;
use Oro\Bundle\CheckoutBundle\Migrations\Schema\RemoveWorkflowFieldsMigration;

class PostUpMigrationListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testOnPostUp()
    {
        $event = $this->getMockBuilder('Oro\Bundle\MigrationBundle\Event\PostMigrationEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('addMigration')
            ->with($this->isInstanceOf(RemoveWorkflowFieldsMigration::class), true);

        $listener = new PostUpMigrationListener();
        $listener->onPostUp($event);
    }
}
