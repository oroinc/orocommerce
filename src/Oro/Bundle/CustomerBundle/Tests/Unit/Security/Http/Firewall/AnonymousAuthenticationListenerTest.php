<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Security\Http\Firewall;

use Oro\Bundle\CustomerBundle\Security\Http\Firewall\AnonymousAuthenticationListener;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;

class AnonymousAuthenticationListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testHandleWithTokenStorageHavingNotAnonymousToken()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|TokenStorageInterface $tokenStorage */
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->expects($this->any())
            ->method('getToken')
            ->will($this->returnValue($this->createMock(TokenInterface::class)));
        $tokenStorage->expects($this->never())
            ->method('setToken');

        $authenticationManager = $this->createMock(AuthenticationManagerInterface::class);
        $authenticationManager->expects($this->never())
            ->method('authenticate');

        /** @var \PHPUnit_Framework_MockObject_MockObject|GetResponseEvent $event */
        $event = $this->createMock(GetResponseEvent::class);
        
        /** @var ListenerInterface|\PHPUnit_Framework_MockObject_MockObject $baseListener */
        $baseListener = $this->createMock(ListenerInterface::class);
        $baseListener->expects($this->once())
            ->method('handle')
            ->with($event);
        
        $listener = new AnonymousAuthenticationListener($baseListener, $tokenStorage, null, $authenticationManager);
        $listener->handle($event);
    }

    public function testHandleWithTokenStorageHavingAnonymousToken()
    {
        $anonymousToken = $this->createMock(AnonymousToken::class);
        $anonymousToken->expects($this->any())
            ->method('getSecret')
            ->will($this->returnValue('TheSecret'));
        $anonymousToken->expects($this->any())
            ->method('getUser')
            ->will($this->returnValue('anon.'));
        $anonymousToken->expects($this->any())
            ->method('getRoles')
            ->will($this->returnValue(['ROLE_SOME']));

        $newAnonymousToken = new AnonymousToken(
            'TheSecret',
            'anon.',
            ['ROLE_FRONTEND_ANONYMOUS', 'ROLE_SOME']
        );

        /** @var \PHPUnit_Framework_MockObject_MockObject|TokenStorageInterface $tokenStorage */
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->expects($this->any())
            ->method('getToken')
            ->will($this->returnValue($anonymousToken));
        $tokenStorage->expects($this->once())
            ->method('setToken');

        $authenticationManager = $this->createMock(AuthenticationManagerInterface::class);
        $authenticationManager->expects($this->once())
            ->method('authenticate')
            ->with($newAnonymousToken);

        /** @var \PHPUnit_Framework_MockObject_MockObject|GetResponseEvent $event */
        $event = $this->createMock(GetResponseEvent::class);
        
        /** @var ListenerInterface|\PHPUnit_Framework_MockObject_MockObject $baseListener */
        $baseListener = $this->createMock(ListenerInterface::class);
        
        $listener = new AnonymousAuthenticationListener($baseListener, $tokenStorage, null, $authenticationManager);
        $listener->handle($event);
    }

    public function testHandledEventIsLogged()
    {
        $anonymousToken = $this->createMock(AnonymousToken::class);
        $anonymousToken->expects($this->any())
            ->method('getSecret')
            ->will($this->returnValue('TheSecret'));
        $anonymousToken->expects($this->any())
            ->method('getUser')
            ->will($this->returnValue('anon.'));
        $anonymousToken->expects($this->any())
            ->method('getRoles')
            ->will($this->returnValue(['ROLE_SOME']));

        /** @var \PHPUnit_Framework_MockObject_MockObject|TokenStorageInterface $tokenStorage */
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->expects($this->any())
            ->method('getToken')
            ->will($this->returnValue($anonymousToken));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info')
            ->with('Populated the TokenStorage with an frontend anonymous Token.');

        /** @var \PHPUnit_Framework_MockObject_MockObject|GetResponseEvent $event */
        $event = $this->createMock(GetResponseEvent::class);
        
        /** @var ListenerInterface|\PHPUnit_Framework_MockObject_MockObject $baseListener */
        $baseListener = $this->createMock(ListenerInterface::class);
        
        $listener = new AnonymousAuthenticationListener($baseListener, $tokenStorage, $logger);
        $listener->handle($event);
    }

    public function testShouldCatchAndLogException()
    {
        $exception = new AuthenticationException('Some error');

        $anonymousToken = $this->createMock(AnonymousToken::class);
        $anonymousToken->expects($this->any())
            ->method('getSecret')
            ->will($this->throwException($exception));

        /** @var \PHPUnit_Framework_MockObject_MockObject|TokenStorageInterface $tokenStorage */
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->expects($this->any())
            ->method('getToken')
            ->will($this->returnValue($anonymousToken));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
           ->method('info')
           ->with('Frontend anonymous authentication failed.', ['exception' => $exception]);

        /** @var \PHPUnit_Framework_MockObject_MockObject|GetResponseEvent $event */
        $event = $this->createMock(GetResponseEvent::class);
        $baseListener = $this->createMock(ListenerInterface::class);
        
        /** @var ListenerInterface|\PHPUnit_Framework_MockObject_MockObject $baseListener */
        $listener = new AnonymousAuthenticationListener($baseListener, $tokenStorage, $logger);
        $listener->handle($event);
    }
}
