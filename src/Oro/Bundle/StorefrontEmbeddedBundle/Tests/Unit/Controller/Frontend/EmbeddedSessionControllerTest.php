<?php

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\Controller\Frontend;

use League\OAuth2\Server\AuthorizationValidators\AuthorizationValidatorInterface;
use Oro\Bundle\CustomerBundle\Security\AnonymousCustomerUserAuthenticator;
use Oro\Bundle\CustomerBundle\Security\Firewall\CustomerVisitorCookieFactory;
use Oro\Bundle\CustomerBundle\Security\LoginManager;
use Oro\Bundle\OAuth2ServerBundle\Entity\Client;
use Oro\Bundle\OAuth2ServerBundle\Entity\Manager\ClientManager;
use Oro\Bundle\StorefrontEmbeddedBundle\Controller\Frontend\EmbeddedSessionController;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class EmbeddedSessionControllerTest extends TestCase
{
    private const string CLIENT_ID = 'storefront-client';
    private const string USER_IDENTIFIER = 'AmandaRCole@example.org';
    private const string VISITOR_SESSION_ID = 'bac0d8a3996cf3e02769';

    private AuthorizationValidatorInterface&MockObject $authorizationValidator;
    private ClientManager&MockObject $clientManager;
    private UserProviderInterface&MockObject $userProvider;
    private LoginManager&MockObject $loginManager;
    private CustomerVisitorCookieFactory&MockObject $visitorCookieFactory;
    private EmbeddedSessionController $controller;

    #[\Override]
    protected function setUp(): void
    {
        $httpMessageFactory = $this->createMock(HttpMessageFactoryInterface::class);
        $httpMessageFactory->expects(self::any())
            ->method('createRequest')
            ->willReturn($this->createMock(ServerRequestInterface::class));

        $this->authorizationValidator = $this->createMock(AuthorizationValidatorInterface::class);
        $this->clientManager = $this->createMock(ClientManager::class);
        $this->userProvider = $this->createMock(UserProviderInterface::class);
        $this->loginManager = $this->createMock(LoginManager::class);
        $this->visitorCookieFactory = $this->createMock(CustomerVisitorCookieFactory::class);

        $this->controller = new EmbeddedSessionController(
            $httpMessageFactory,
            $this->authorizationValidator,
            $this->clientManager,
            $this->userProvider,
            $this->loginManager,
            $this->createMock(TokenStorageInterface::class),
            $this->visitorCookieFactory
        );
    }

    private function expectValidatedStorefrontToken(string $userIdentifier = self::USER_IDENTIFIER): void
    {
        $validated = $this->createMock(ServerRequestInterface::class);
        $validated->expects(self::any())
            ->method('getAttribute')
            ->willReturnMap([
                ['oauth_client_id', null, self::CLIENT_ID],
                ['oauth_user_id', null, $userIdentifier],
            ]);

        $this->authorizationValidator->expects(self::once())
            ->method('validateAuthorization')
            ->willReturn($validated);

        $client = $this->createMock(Client::class);
        $client->expects(self::any())->method('isActive')->willReturn(true);
        $client->expects(self::any())->method('isFrontend')->willReturn(true);

        $this->clientManager->expects(self::once())
            ->method('getClient')
            ->with(self::CLIENT_ID)
            ->willReturn($client);
    }

    public function testRefusesTheTokenOfAUserThatNoLongerExistsInsteadOfFailing(): void
    {
        $this->expectValidatedStorefrontToken();

        $this->userProvider->expects(self::once())
            ->method('loadUserByIdentifier')
            ->with(self::USER_IDENTIFIER)
            ->willThrowException(new UserNotFoundException());

        $this->loginManager->expects(self::never())->method('logInUser');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('A storefront OAuth2 access token is required.');

        $this->controller->bootstrapAction(Request::create('/embedded/session/', 'POST'));
    }

    public function testAnswersGuestTokenWithTheVisitorCookieOfThatToken(): void
    {
        $this->expectValidatedStorefrontToken('visitor:' . self::VISITOR_SESSION_ID);

        $visitorCookie = Cookie::create('customer_visitor', 'cookie-of-the-token-visitor');
        $this->visitorCookieFactory->expects(self::once())
            ->method('getCookie')
            ->with(self::VISITOR_SESSION_ID)
            ->willReturn($visitorCookie);

        $this->userProvider->expects(self::never())->method('loadUserByIdentifier');
        $this->loginManager->expects(self::never())->method('logInUser');

        $request = Request::create('/embedded/session/', 'POST');
        $response = $this->controller->bootstrapAction($request);

        self::assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        self::assertSame(
            $visitorCookie,
            $request->attributes->get(AnonymousCustomerUserAuthenticator::COOKIE_ATTR_NAME)
        );
    }
}
