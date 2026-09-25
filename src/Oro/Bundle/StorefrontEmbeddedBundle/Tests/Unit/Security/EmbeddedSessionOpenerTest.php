<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\Security;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Security\AnonymousCustomerUserAuthenticator;
use Oro\Bundle\CustomerBundle\Security\Firewall\CustomerVisitorCookieFactory;
use Oro\Bundle\CustomerBundle\Security\LoginManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\StorefrontEmbeddedBundle\Security\EmbeddedSessionOpener;
use Oro\Bundle\StorefrontEmbeddedBundle\Security\StorefrontAccessToken;
use Oro\Bundle\StorefrontEmbeddedBundle\Security\StorefrontAccessTokenResolver;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class EmbeddedSessionOpenerTest extends TestCase
{
    use LoggerAwareTraitTestTrait;

    private const string CUSTOMER_USER_IDENTIFIER = 'customer@example.test';
    private const string VISITOR_IDENTIFIER = 'visitor:abc123';

    private WebsiteManager&MockObject $websiteManager;

    private UserProviderInterface&MockObject $userProvider;

    private UserCheckerInterface&MockObject $userChecker;

    private LoginManager&MockObject $loginManager;

    private CustomerVisitorCookieFactory&MockObject $visitorCookieFactory;

    private EmbeddedSessionOpener $sessionOpener;

    #[\Override]
    protected function setUp(): void
    {
        $this->websiteManager = $this->createMock(WebsiteManager::class);
        $this->userProvider = $this->createMock(UserProviderInterface::class);
        $this->userChecker = $this->createMock(UserCheckerInterface::class);
        $this->loginManager = $this->createMock(LoginManager::class);
        $this->visitorCookieFactory = $this->createMock(CustomerVisitorCookieFactory::class);

        $this->sessionOpener = new EmbeddedSessionOpener(
            $this->websiteManager,
            $this->userProvider,
            $this->userChecker,
            $this->loginManager,
            $this->visitorCookieFactory
        );
        $this->setUpLoggerMock($this->sessionOpener);
    }

    public function testOpenThrowsUnauthorizedWhenThereIsNoCurrentWebsite(): void
    {
        $this->websiteManager->method('getCurrentWebsite')
            ->willReturn(null);
        $this->userProvider->expects(self::never())
            ->method('loadUserByIdentifier');

        $this->assertLoggerNoticeMethodCalled();

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage(StorefrontAccessTokenResolver::TOKEN_REQUIRED_MESSAGE);

        $this->sessionOpener->open(Request::create('/'), new StorefrontAccessToken(self::CUSTOMER_USER_IDENTIFIER, $this->createOrganization(1)));
    }

    public function testOpenThrowsUnauthorizedWhenTheCurrentWebsiteBelongsToAnotherOrganization(): void
    {
        $website = new Website();
        $website->setOrganization($this->createOrganization(2));
        $this->websiteManager->method('getCurrentWebsite')
            ->willReturn($website);
        $this->userProvider->expects(self::never())
            ->method('loadUserByIdentifier');

        $this->assertLoggerNoticeMethodCalled();

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage(StorefrontAccessTokenResolver::TOKEN_REQUIRED_MESSAGE);

        $this->sessionOpener->open(Request::create('/'), new StorefrontAccessToken(self::CUSTOMER_USER_IDENTIFIER, $this->createOrganization(1)));
    }

    public function testOpenSendsTheVisitorCookieForAVisitorIdentifierWithoutCallingLoginManager(): void
    {
        $organization = $this->createOrganization(1);
        $website = new Website();
        $website->setOrganization($organization);
        $this->websiteManager->method('getCurrentWebsite')
            ->willReturn($website);
        $this->userProvider->expects(self::never())
            ->method('loadUserByIdentifier');
        $this->loginManager->expects(self::never())
            ->method('logInUser');

        $cookie = new Cookie(AnonymousCustomerUserAuthenticator::COOKIE_NAME, 'abc123');
        $this->visitorCookieFactory->expects(self::once())
            ->method('getCookie')
            ->with('abc123')
            ->willReturn($cookie);

        $request = Request::create('/');
        $this->sessionOpener->open($request, new StorefrontAccessToken(self::VISITOR_IDENTIFIER, $organization));

        self::assertSame($cookie, $request->attributes->get(AnonymousCustomerUserAuthenticator::COOKIE_ATTR_NAME));
    }

    public function testOpenThrowsUnauthorizedWhenTheLoadedUserIsNotACustomerUser(): void
    {
        $organization = $this->createOrganization(1);
        $website = new Website();
        $website->setOrganization($organization);
        $this->websiteManager->method('getCurrentWebsite')
            ->willReturn($website);
        $this->userProvider->method('loadUserByIdentifier')
            ->with(self::CUSTOMER_USER_IDENTIFIER)
            ->willReturn($this->createMock(UserInterface::class));
        $this->loginManager->expects(self::never())
            ->method('logInUser');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage(StorefrontAccessTokenResolver::TOKEN_REQUIRED_MESSAGE);

        $this->sessionOpener->open(Request::create('/'), new StorefrontAccessToken(self::CUSTOMER_USER_IDENTIFIER, $organization));
    }

    public function testOpenThrowsUnauthorizedWhenLoadingTheUserThrowsAnAuthenticationException(): void
    {
        $organization = $this->createOrganization(1);
        $website = new Website();
        $website->setOrganization($organization);
        $this->websiteManager->method('getCurrentWebsite')
            ->willReturn($website);
        $this->userProvider->method('loadUserByIdentifier')
            ->willThrowException(new AuthenticationException('No such user.'));
        $this->loginManager->expects(self::never())
            ->method('logInUser');

        $this->assertLoggerNoticeMethodCalled();

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage(StorefrontAccessTokenResolver::TOKEN_REQUIRED_MESSAGE);

        $this->sessionOpener->open(Request::create('/'), new StorefrontAccessToken(self::CUSTOMER_USER_IDENTIFIER, $organization));
    }

    public function testOpenThrowsUnauthorizedWhenTheCustomerUserDoesNotBelongToTheTokenOrganization(): void
    {
        $tokenOrganization = $this->createOrganization(1);
        $userOrganization = $this->createOrganization(2);
        $website = new Website();
        $website->setOrganization($tokenOrganization);
        $this->websiteManager->method('getCurrentWebsite')
            ->willReturn($website);

        $user = new CustomerUser();
        $user->setOrganization($userOrganization);
        $this->userProvider->method('loadUserByIdentifier')
            ->willReturn($user);
        $this->userChecker->expects(self::never())
            ->method('checkPreAuth');
        $this->loginManager->expects(self::never())
            ->method('logInUser');

        $this->assertLoggerNoticeMethodCalled();

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage(StorefrontAccessTokenResolver::TOKEN_REQUIRED_MESSAGE);

        $this->sessionOpener->open(Request::create('/'), new StorefrontAccessToken(self::CUSTOMER_USER_IDENTIFIER, $tokenOrganization));
    }

    public function testOpenThrowsUnauthorizedWithTheSessionRefusedMessageWhenThePreAuthCheckRejectsTheAccount(): void
    {
        $organization = $this->createOrganization(1);
        $website = new Website();
        $website->setOrganization($organization);
        $this->websiteManager->method('getCurrentWebsite')
            ->willReturn($website);

        $user = new CustomerUser();
        $user->setOrganization($organization);
        $this->userProvider->method('loadUserByIdentifier')
            ->willReturn($user);
        $this->userChecker->method('checkPreAuth')
            ->willThrowException(new DisabledException('Account is disabled.'));
        $this->loginManager->expects(self::never())
            ->method('logInUser');

        $this->assertLoggerNoticeMethodCalled();

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('The account cannot start a storefront session.');

        $this->sessionOpener->open(Request::create('/'), new StorefrontAccessToken(self::CUSTOMER_USER_IDENTIFIER, $organization));
    }

    public function testOpenThrowsUnauthorizedWithTheSessionRefusedMessageWhenThePostAuthCheckRejectsTheAccount(): void
    {
        $organization = $this->createOrganization(1);
        $website = new Website();
        $website->setOrganization($organization);
        $this->websiteManager->method('getCurrentWebsite')
            ->willReturn($website);

        $user = new CustomerUser();
        $user->setOrganization($organization);
        $this->userProvider->method('loadUserByIdentifier')
            ->willReturn($user);
        $this->userChecker->method('checkPostAuth')
            ->willThrowException(new DisabledException('Account is disabled.'));
        $this->loginManager->expects(self::never())
            ->method('logInUser');

        $this->assertLoggerNoticeMethodCalled();

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('The account cannot start a storefront session.');

        $this->sessionOpener->open(Request::create('/'), new StorefrontAccessToken(self::CUSTOMER_USER_IDENTIFIER, $organization));
    }

    public function testOpenLogsInTheCustomerUserExactlyOnceOnTheHappyPath(): void
    {
        $organization = $this->createOrganization(1);
        $website = new Website();
        $website->setOrganization($organization);
        $this->websiteManager->method('getCurrentWebsite')
            ->willReturn($website);

        $user = new CustomerUser();
        $user->setOrganization($organization);
        $this->userProvider->method('loadUserByIdentifier')
            ->with(self::CUSTOMER_USER_IDENTIFIER)
            ->willReturn($user);

        $this->loginManager->expects(self::once())
            ->method('logInUser')
            ->with('frontend', $user);

        $this->sessionOpener->open(Request::create('/'), new StorefrontAccessToken(self::CUSTOMER_USER_IDENTIFIER, $organization));
    }

    private function createOrganization(int $id): Organization
    {
        $organization = new Organization();
        ReflectionUtil::setId($organization, $id);

        return $organization;
    }
}
