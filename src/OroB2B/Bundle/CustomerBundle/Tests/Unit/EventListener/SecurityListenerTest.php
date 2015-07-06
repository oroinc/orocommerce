<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Unit\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

use OroB2B\Bundle\CustomerBundle\EventListener\LoginListener;

class SecurityListenerTest extends \PHPUnit_Framework_TestCase
{
    const TEST_URL = 'http://test_url/';

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|InteractiveLoginEvent
     */
    protected $event;

    /**
     * @var LoginListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->request = Request::create(self::TEST_URL);

        $this->event = $this->getMockBuilder('Symfony\Component\Security\Http\Event\InteractiveLoginEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $this->event->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->request);

        $this->listener = new LoginListener();
    }

    protected function tearDown()
    {
        unset($this->request, $this->event, $this->listener);
    }

    public function testOnSuccessLogin()
    {
        $this->assertNull($this->request->attributes->get('_fullRedirect'));

        $this->listener->onSecurityInteractiveLogin($this->event);

        $this->assertTrue($this->request->attributes->get('_fullRedirect'));
    }
}
