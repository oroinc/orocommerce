<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Async\Extension;

use Oro\Bundle\VisibilityBundle\Async\Extension\VisibilityMessageHandlerExtension;
use Oro\Bundle\VisibilityBundle\Model\VisibilityMessageHandler;
use Oro\Component\MessageQueue\Consumption\Context;

class VisibilityMessageHandlerExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testOnPostReceived(): void
    {
        $visibilityMessageHandler = $this->createMock(VisibilityMessageHandler::class);

        $extension = new VisibilityMessageHandlerExtension($visibilityMessageHandler);

        $visibilityMessageHandler
            ->expects($this->once())
            ->method('sendScheduledMessages');

        $extension->onPostReceived($this->createMock(Context::class));
    }
}
