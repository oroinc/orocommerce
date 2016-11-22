<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\NotificationMessage\Renderer;

use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

use Oro\Bundle\PricingBundle\NotificationMessage\Message;
use Oro\Bundle\PricingBundle\NotificationMessage\Renderer\FlashMessageRenderer;

class FlashMessageRendererTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FlashBagInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $flashBag;

    /**
     * @var FlashMessageRenderer
     */
    protected $flashMessageRenderer;

    protected function setUp()
    {
        $this->flashBag = $this->getMock(FlashBagInterface::class);
        $this->flashMessageRenderer = new FlashMessageRenderer($this->flashBag);
    }

    public function testRenderDefaultStatus()
    {
        /** @var Message|\PHPUnit_Framework_MockObject_MockObject $message **/
        $message = $this->getMockBuilder(Message::class)
            ->disableOriginalConstructor()
            ->getMock();

        $message->expects($this->any())
            ->method('getStatus')
            ->willReturn('unsupported_status');
        $message->expects($this->any())
            ->method('getMessage')
            ->willReturn('MSG');

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with(FlashMessageRenderer::DEFAULT_STATUS, 'MSG');

        $this->flashMessageRenderer->render($message);
    }

    public function testRenderKnownStatus()
    {
        /** @var Message|\PHPUnit_Framework_MockObject_MockObject $message **/
        $message = $this->getMockBuilder(Message::class)
            ->disableOriginalConstructor()
            ->getMock();

        $message->expects($this->any())
            ->method('getStatus')
            ->willReturn('error');
        $message->expects($this->any())
            ->method('getMessage')
            ->willReturn('MSG');

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('error', 'MSG');

        $this->flashMessageRenderer->render($message);
    }
}
