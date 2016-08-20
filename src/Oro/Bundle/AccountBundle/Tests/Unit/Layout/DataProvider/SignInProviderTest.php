<?php

namespace Oro\Bundle\AccountBundle\Tests\Unit\Layout\DataProvider;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\AccountBundle\Entity\AccountUser;
use Oro\Bundle\AccountBundle\Layout\DataProvider\SignInProvider;

class SignInProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var SignInProvider */
    protected $dataProvider;

    /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject */
    protected $requestStack;

    /** @var Request|\PHPUnit_Framework_MockObject_MockObject */
    protected $request;

    /** @var ParameterBag|\PHPUnit_Framework_MockObject_MockObject */
    protected $parameterBag;

    /** @var SecurityFacade|\PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var CsrfTokenManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $csrfTokenManager;

    protected function setUp()
    {
        $this->parameterBag = $this->getMock(ParameterBag::class);

        $this->request = $this->getMock(Request::class);
        $this->request->attributes = $this->parameterBag;

        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $this->requestStack = $this->getMock(RequestStack::class);
        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->will($this->returnValue($this->request));

        $this->securityFacade = $this->getMock(SecurityFacade::class, [], [], '', false);
        $this->csrfTokenManager = $this->getMock(CsrfTokenManagerInterface::class);

        $this->dataProvider = new SignInProvider($this->requestStack, $this->securityFacade, $this->csrfTokenManager);
    }

    public function testGetLastNameWithSession()
    {
        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session */
        $session = $this->getMock(SessionInterface::class);
        $this->request
            ->expects($this->once())
            ->method('getSession')
            ->will($this->returnValue($session));

        $session->expects($this->once())
            ->method('get')
            ->with(Security::LAST_USERNAME)
            ->will($this->returnValue('last_name'));

        $this->assertEquals('last_name', $this->dataProvider->getLastName());
        /** test local cache */
        $this->assertEquals('last_name', $this->dataProvider->getLastName());
    }

    public function testGetLastNameWithoutSession()
    {
        $dataProvider = new SignInProvider($this->requestStack, $this->securityFacade, $this->csrfTokenManager);
        $this->assertEquals('', $dataProvider->getLastName());
        /** test local cache */
        $this->assertEquals('', $dataProvider->getLastName());
    }

    public function testGetErrorWithSession()
    {
        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session */
        $session = $this->getMock(SessionInterface::class);
        $this->request
            ->expects($this->once())
            ->method('getSession')
            ->will($this->returnValue($session));

        $session->expects($this->once())
            ->method('has')
            ->with(Security::AUTHENTICATION_ERROR)
            ->will($this->returnValue(true));

        $session->expects($this->once())
            ->method('get')
            ->with(Security::AUTHENTICATION_ERROR)
            ->will($this->returnValue(new AuthenticationException('error')));

        $this->assertEquals('error', $this->dataProvider->getError());
        /** test local cache */
        $this->assertEquals('error', $this->dataProvider->getError());
    }

    public function testGetErrorWithoutSession()
    {
        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session */
        $session = $this->getMock(SessionInterface::class);
        $this->request
            ->expects($this->once())
            ->method('getSession')
            ->will($this->returnValue($session));

        $dataProvider = new SignInProvider($this->requestStack, $this->securityFacade, $this->csrfTokenManager);
        $this->assertEquals('', $dataProvider->getError());
        /** test local cache */
        $this->assertEquals('', $dataProvider->getError());
    }

    public function testGetErrorFromRequestAttributes()
    {
        $dataProvider = new SignInProvider($this->requestStack, $this->securityFacade, $this->csrfTokenManager);

        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session */
        $session = $this->getMock(SessionInterface::class);
        $this->request
            ->expects($this->once())
            ->method('getSession')
            ->will($this->returnValue($session));

        $this->parameterBag
            ->expects($this->once())
            ->method('has')
            ->with(Security::AUTHENTICATION_ERROR)
            ->will($this->returnValue(true));

        $this->parameterBag
            ->expects($this->once())
            ->method('get')
            ->with(Security::AUTHENTICATION_ERROR)
            ->will($this->returnValue(new AuthenticationException('error')));

        $this->assertEquals('error', $dataProvider->getError());
        /** test local cache */
        $this->assertEquals('error', $dataProvider->getError());
    }

    public function testGetCSRFToken()
    {
        /** @var CsrfToken|\PHPUnit_Framework_MockObject_MockObject $csrfToken */
        $csrfToken = $this->getMock(CsrfToken::class, [], [], '', false);
        $csrfToken->expects($this->once())
            ->method('getValue')
            ->will($this->returnValue('csrf_token'));

        $this->csrfTokenManager
            ->expects($this->once())
            ->method('getToken')
            ->with('authenticate')
            ->will($this->returnValue($csrfToken));

        $this->assertEquals('csrf_token', $this->dataProvider->getCSRFToken());
        /** test local cache */
        $this->assertEquals('csrf_token', $this->dataProvider->getCSRFToken());
    }

    public function testGetLoggedUser()
    {
        $accountUser = new AccountUser();

        $this->securityFacade
            ->expects($this->once())
            ->method('getLoggedUser')
            ->will($this->returnValue($accountUser));

        $this->assertEquals($accountUser, $this->dataProvider->getLoggedUser());
    }
}
