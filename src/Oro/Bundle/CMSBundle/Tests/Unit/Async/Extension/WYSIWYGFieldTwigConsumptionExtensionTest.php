<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Async\Extension;

use Oro\Bundle\CMSBundle\Async\Extension\WYSIWYGFieldTwigConsumptionExtension;
use Oro\Bundle\CMSBundle\EventListener\WYSIWYGFieldTwigListener;
use Oro\Component\MessageQueue\Consumption\Context;

class WYSIWYGFieldTwigConsumptionExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var WYSIWYGFieldTwigListener|\PHPUnit\Framework\MockObject\MockObject */
    private $wysiwygFieldTwigListener;

    /** @var WYSIWYGFieldTwigConsumptionExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->wysiwygFieldTwigListener = $this->createMock(WYSIWYGFieldTwigListener::class);

        $this->extension = new WYSIWYGFieldTwigConsumptionExtension($this->wysiwygFieldTwigListener);
    }

    public function testOnPostReceived(): void
    {
        $this->wysiwygFieldTwigListener->expects($this->once())
            ->method('onTerminate');

        $this->extension->onPostReceived($this->createMock(Context::class));
    }
}
