<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Unit\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

use Oro\Bundle\UserBundle\Entity\User;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\CustomerBundle\EventListener\LoginListener;

class LoginListenerTest extends \PHPUnit_Framework_TestCase
{
    const TEST_URL = 'http://test_url/';

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TokenInterface
     */
    protected $token;

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

        $this->token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');

        $this->event = $this->getMockBuilder('Symfony\Component\Security\Http\Event\InteractiveLoginEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new LoginListener();
    }

    protected function tearDown()
    {
        unset($this->request, $this->token, $this->event, $this->listener);
    }

    /**
     * @dataProvider dataProvider
     *
     * @param UserInterface $user
     * @param bool $expected
     */
    public function testOnSuccessLogin(UserInterface $user, $expected)
    {
        $this->token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->event->expects($this->once())
            ->method('getAuthenticationToken')
            ->willReturn($this->token);
        $this->event->expects($expected ? $this->once() : $this->never())
            ->method('getRequest')
            ->willReturn($this->request);

        $this->assertNull($this->request->attributes->get('_fullRedirect'));

        $this->listener->onSecurityInteractiveLogin($this->event);

        $this->assertEquals($expected, $this->request->attributes->get('_fullRedirect'));
    }

    /**
     * @return array
     */
    public function dataProvider()
    {
        return [
            'backend user' => [
                'user' => new User(),
                'expected' => null,
            ],
            'account user' => [
                'user' => new AccountUser(),
                'expected' => true,
            ],
        ];
    }
}
