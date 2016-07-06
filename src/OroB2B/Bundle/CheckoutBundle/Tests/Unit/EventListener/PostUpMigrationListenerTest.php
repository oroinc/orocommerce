<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use OroB2B\Bundle\CheckoutBundle\EventListener\PostUpMigrationListener;
use OroB2B\Bundle\CheckoutBundle\Migrations\Schema\RemoveWorkflowFieldsMigration;

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
