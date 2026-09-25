<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\Security;

use League\OAuth2\Server\AuthorizationValidators\AuthorizationValidatorInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use Oro\Bundle\OAuth2ServerBundle\Entity\Client;
use Oro\Bundle\OAuth2ServerBundle\Entity\Manager\ClientManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\StorefrontEmbeddedBundle\Security\StorefrontAccessTokenResolver;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final class StorefrontAccessTokenResolverTest extends TestCase
{
    use LoggerAwareTraitTestTrait;

    private const string CLIENT_ID = 'storefront-client';
    private const string USER_IDENTIFIER = 'customer@example.test';

    private HttpMessageFactoryInterface&MockObject $httpMessageFactory;

    private AuthorizationValidatorInterface&MockObject $authorizationValidator;

    private ClientManager&MockObject $clientManager;

    private StorefrontAccessTokenResolver $resolver;

    #[\Override]
    protected function setUp(): void
    {
        $this->httpMessageFactory = $this->createMock(HttpMessageFactoryInterface::class);
        $this->authorizationValidator = $this->createMock(AuthorizationValidatorInterface::class);
        $this->clientManager = $this->createMock(ClientManager::class);

        $this->resolver = new StorefrontAccessTokenResolver(
            $this->httpMessageFactory,
            $this->authorizationValidator,
            $this->clientManager
        );
        $this->setUpLoggerMock($this->resolver);
    }

    public function testResolveThrowsUnauthorizedWhenTheValidatorRejectsTheToken(): void
    {
        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $this->httpMessageFactory->method('createRequest')
            ->willReturn($serverRequest);
        $this->authorizationValidator->method('validateAuthorization')
            ->with($serverRequest)
            ->willThrowException(new OAuthServerException('Invalid token', 6, 'invalid_grant'));

        $this->assertLoggerInfoMethodCalled();

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage(StorefrontAccessTokenResolver::TOKEN_REQUIRED_MESSAGE);

        $this->resolver->resolve(Request::create('/'));
    }

    public function testResolveThrowsUnauthorizedWhenTheValidatedRequestHasNoAccessTokenId(): void
    {
        $this->httpMessageFactory->method('createRequest')
            ->willReturn($this->createMock(ServerRequestInterface::class));
        $validatedRequest = $this->createMock(ServerRequestInterface::class);
        $validatedRequest->method('getAttribute')
            ->with('oauth_access_token_id')
            ->willReturn(null);
        $this->authorizationValidator->method('validateAuthorization')
            ->willReturn($validatedRequest);
        $this->clientManager->expects(self::never())
            ->method('getClient');

        $this->assertLoggerNoticeMethodCalled();

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage(StorefrontAccessTokenResolver::TOKEN_REQUIRED_MESSAGE);

        $this->resolver->resolve(Request::create('/'));
    }

    /**
     * @dataProvider rejectedClientProvider
     */
    public function testResolveThrowsUnauthorizedWhenTheClientIsRejected(?Client $client): void
    {
        $this->httpMessageFactory->method('createRequest')
            ->willReturn($this->createMock(ServerRequestInterface::class));
        $validatedRequest = $this->createMock(ServerRequestInterface::class);
        $validatedRequest->method('getAttribute')
            ->willReturnMap([
                ['oauth_access_token_id', null, 'token-id'],
                ['oauth_client_id', null, self::CLIENT_ID],
            ]);
        $this->authorizationValidator->method('validateAuthorization')
            ->willReturn($validatedRequest);
        $this->clientManager->method('getClient')
            ->with(self::CLIENT_ID)
            ->willReturn($client);

        $this->assertLoggerNoticeMethodCalled();

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage(StorefrontAccessTokenResolver::TOKEN_REQUIRED_MESSAGE);

        $this->resolver->resolve(Request::create('/'));
    }

    public static function rejectedClientProvider(): iterable
    {
        yield 'client not found' => [null];

        $inactiveClient = new Client();
        $inactiveClient->setActive(false);
        $inactiveClient->setFrontend(true);
        $inactiveClient->setOrganization(self::createEnabledOrganization());
        yield 'inactive client' => [$inactiveClient];

        $backOfficeClient = new Client();
        $backOfficeClient->setActive(true);
        $backOfficeClient->setFrontend(false);
        $backOfficeClient->setOrganization(self::createEnabledOrganization());
        yield 'non-frontend client' => [$backOfficeClient];

        $orphanClient = new Client();
        $orphanClient->setActive(true);
        $orphanClient->setFrontend(true);
        yield 'client without an organization' => [$orphanClient];

        $disabledOrganization = new Organization();
        ReflectionUtil::setId($disabledOrganization, 5);
        $disabledOrganization->setEnabled(false);
        $disabledOrgClient = new Client();
        $disabledOrgClient->setActive(true);
        $disabledOrgClient->setFrontend(true);
        $disabledOrgClient->setOrganization($disabledOrganization);
        yield 'client of a disabled organization' => [$disabledOrgClient];
    }

    private static function createEnabledOrganization(): Organization
    {
        $organization = new Organization();
        ReflectionUtil::setId($organization, 1);
        $organization->setEnabled(true);

        return $organization;
    }

    /**
     * @dataProvider blankUserIdentifierProvider
     */
    public function testResolveThrowsUnauthorizedWhenTheUserIdentifierIsBlank(mixed $userIdentifier): void
    {
        $this->httpMessageFactory->method('createRequest')
            ->willReturn($this->createMock(ServerRequestInterface::class));
        $validatedRequest = $this->createMock(ServerRequestInterface::class);
        $validatedRequest->method('getAttribute')
            ->willReturnMap([
                ['oauth_access_token_id', null, 'token-id'],
                ['oauth_client_id', null, self::CLIENT_ID],
                ['oauth_user_id', null, $userIdentifier],
            ]);
        $this->authorizationValidator->method('validateAuthorization')
            ->willReturn($validatedRequest);

        $client = new Client();
        $client->setActive(true);
        $client->setFrontend(true);
        $client->setOrganization(self::createEnabledOrganization());
        $this->clientManager->method('getClient')
            ->willReturn($client);

        $this->assertLoggerNoticeMethodCalled();

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage(StorefrontAccessTokenResolver::TOKEN_REQUIRED_MESSAGE);

        $this->resolver->resolve(Request::create('/'));
    }

    public static function blankUserIdentifierProvider(): iterable
    {
        yield 'empty string' => [''];
        yield 'null' => [null];
        yield 'integer' => [42];
    }

    public function testResolveReturnsTheAccessTokenOnTheHappyPath(): void
    {
        $request = Request::create('/');
        $serverRequest = $this->createMock(ServerRequestInterface::class);
        $this->httpMessageFactory->method('createRequest')
            ->with($request)
            ->willReturn($serverRequest);

        $validatedRequest = $this->createMock(ServerRequestInterface::class);
        $validatedRequest->method('getAttribute')
            ->willReturnMap([
                ['oauth_access_token_id', null, 'token-id'],
                ['oauth_client_id', null, self::CLIENT_ID],
                ['oauth_user_id', null, self::USER_IDENTIFIER],
            ]);
        $this->authorizationValidator->method('validateAuthorization')
            ->with($serverRequest)
            ->willReturn($validatedRequest);

        $organization = self::createEnabledOrganization();
        $client = new Client();
        $client->setActive(true);
        $client->setFrontend(true);
        $client->setOrganization($organization);
        $this->clientManager->method('getClient')
            ->with(self::CLIENT_ID)
            ->willReturn($client);

        $this->assertLoggerNotCalled();

        $accessToken = $this->resolver->resolve($request);

        self::assertSame(self::USER_IDENTIFIER, $accessToken->userIdentifier);
        self::assertSame($organization, $accessToken->organization);
    }
}
