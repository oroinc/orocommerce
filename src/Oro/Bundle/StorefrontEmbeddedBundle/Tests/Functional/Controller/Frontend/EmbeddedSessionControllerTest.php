<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Security\AnonymousCustomerUserAuthenticator;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\OAuth2ServerBundle\Tests\Functional\DataFixtures\LoadFrontendPasswordGrantClient;
use Oro\Bundle\StorefrontEmbeddedBundle\Controller\Frontend\EmbeddedFragmentController;
use Oro\Bundle\StorefrontEmbeddedBundle\Tests\Functional\DataFixtures\LoadStorefrontAuthorizationCodeClient;
use Oro\Bundle\StorefrontEmbeddedBundle\Tests\Functional\EmbeddedSessionTransferTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests the embedded session flow through the OAuth2 session transfer: the normal flow for customer users
 * and visitors, the edge cases of the session it opens (stale host sessions, disabled customer users,
 * guest access disabled after the token was issued), and the session target route.
 * The edge cases are integration tests of the embedded use case: the rules come from the OAuth2 server
 * and the customer bundles, the embedded fragment is the probe.
 *
 * @dbIsolationPerTest
 */
class EmbeddedSessionControllerTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;
    use EmbeddedSessionTransferTestTrait;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->restoreConfigValues();
    }

    public function testSessionTransferOpensCustomerUserSessionThatAuthenticatesEmbeddedFragment(): void
    {
        $this->loadCustomerUserFixtures();

        $consumeResponse = $this->openCustomerUserSession(LoadCustomerUserData::EMAIL, LoadCustomerUserData::PASSWORD);

        self::assertSame(
            $this->getUrl('oro_storefront_embedded_session', [], true),
            $consumeResponse->headers->get('Location')
        );
        self::assertNotEmpty(
            $this->findSessionCookie($consumeResponse)?->getValue(),
            'The storefront session cookie must be returned to the host.'
        );
        self::assertStringContainsString(LoadCustomerUserData::EMAIL, $this->getProfileFragmentMarkup());
    }

    public function testSessionTransferOpensCustomerUserSessionWhenGuestAccessIsDisabled(): void
    {
        $this->loadCustomerUserFixtures();
        $this->setConfigValue('oro_frontend.guest_access_enabled', false);

        $consumeResponse = $this->openCustomerUserSession(LoadCustomerUserData::EMAIL, LoadCustomerUserData::PASSWORD);

        self::assertNotEmpty(
            $this->findSessionCookie($consumeResponse)?->getValue(),
            'The storefront session cookie must be returned to the host.'
        );
        self::assertStringContainsString(LoadCustomerUserData::EMAIL, $this->getProfileFragmentMarkup());
    }

    public function testSessionTransferOpensVisitorSessionThatIdentifiesTheSameVisitor(): void
    {
        $this->loadFixtures([LoadStorefrontAuthorizationCodeClient::class]);
        $accessToken = $this->getVisitorAccessToken();
        $sessionTransferToken = $this->getSessionTransferToken(
            LoadStorefrontAuthorizationCodeClient::OAUTH_CLIENT_ID,
            $accessToken
        );
        $this->client->getCookieJar()->clear();

        $consumeResponse = $this->consumeSessionTransferToken($sessionTransferToken);

        self::assertResponseStatusCodeEquals($consumeResponse, Response::HTTP_SEE_OTHER);
        self::assertSame(
            $this->getUrl('oro_storefront_embedded_session', [], true),
            $consumeResponse->headers->get('Location')
        );
        self::assertNotEmpty(
            $this->findSessionCookie($consumeResponse)?->getValue(),
            'The storefront session cookie must be returned to the host.'
        );
        self::assertNotEmpty(
            $this->findCookie($consumeResponse, AnonymousCustomerUserAuthenticator::COOKIE_NAME)?->getValue(),
            'The customer visitor cookie must be returned to the host.'
        );

        $this->client->request('GET', $this->getUrl('oro_frontend_root'));

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_OK);
        $token = self::getContainer()->get('security.token_storage')->getToken();
        self::assertInstanceOf(AnonymousCustomerUserToken::class, $token);
        self::assertSame(
            self::getContainer()->get('oro_oauth2_server.visitor_access_token_parser')
                ->getVisitorSessionId($accessToken),
            $token->getVisitor()->getSessionId()
        );
    }

    public function testConsumeForAnotherCustomerUserReplacesTheStaleSessionTheHostStillKeeps(): void
    {
        $this->loadCustomerUserFixtures();
        $firstSessionId = $this->findSessionCookie(
            $this->openCustomerUserSession(LoadCustomerUserData::EMAIL, LoadCustomerUserData::PASSWORD)
        )?->getValue();
        self::assertNotEmpty($firstSessionId);
        self::assertStringContainsString(LoadCustomerUserData::EMAIL, $this->getProfileFragmentMarkup());
        $staleCookies = $this->client->getCookieJar()->all();

        $sessionTransferToken = $this->getSessionTransferToken(
            LoadFrontendPasswordGrantClient::OAUTH_CLIENT_ID,
            $this->getCustomerUserAccessToken(
                LoadCustomerUserData::LEVEL_1_EMAIL,
                LoadCustomerUserData::LEVEL_1_PASSWORD
            )
        );
        $this->restoreCookies($staleCookies);

        $consumeResponse = $this->consumeSessionTransferToken($sessionTransferToken);

        self::assertResponseStatusCodeEquals($consumeResponse, Response::HTTP_SEE_OTHER);
        $secondSessionId = $this->findSessionCookie($consumeResponse)?->getValue();
        self::assertNotEmpty($secondSessionId);
        self::assertNotSame($firstSessionId, $secondSessionId, 'The session id must change on the new login.');

        $profileMarkup = $this->getProfileFragmentMarkup();
        self::assertStringContainsString(LoadCustomerUserData::LEVEL_1_EMAIL, $profileMarkup);
        self::assertStringNotContainsString(LoadCustomerUserData::EMAIL, $profileMarkup);
    }

    public function testConsumeForVisitorReplacesTheStaleCustomerUserSessionTheHostStillKeeps(): void
    {
        $this->loadFixtures([
            LoadCustomerUserData::class,
            LoadFrontendPasswordGrantClient::class,
            LoadStorefrontAuthorizationCodeClient::class
        ]);
        $this->allowSessionTransferForPasswordGrantClient();
        $this->openCustomerUserSession(LoadCustomerUserData::EMAIL, LoadCustomerUserData::PASSWORD);
        self::assertStringContainsString(LoadCustomerUserData::EMAIL, $this->getProfileFragmentMarkup());
        $staleCookies = $this->client->getCookieJar()->all();

        $this->client->getCookieJar()->clear();
        $sessionTransferToken = $this->getSessionTransferToken(
            LoadStorefrontAuthorizationCodeClient::OAUTH_CLIENT_ID,
            $this->getVisitorAccessToken()
        );
        $this->restoreCookies($staleCookies);

        $consumeResponse = $this->consumeSessionTransferToken($sessionTransferToken);
        self::assertResponseStatusCodeEquals($consumeResponse, Response::HTTP_SEE_OTHER);

        $this->client->request(
            'GET',
            $this->getUrl(EmbeddedFragmentController::ROUTE_NAME, ['block' => 'embedded_profile'])
        );

        $response = $this->client->getResponse();
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
        self::assertStringNotContainsString(LoadCustomerUserData::EMAIL, (string) $response->getContent());
    }

    public function testConsumeForCustomerUserDisabledAfterTheTokenWasIssuedDoesNotOpenSession(): void
    {
        $this->loadCustomerUserFixtures();
        $sessionTransferToken = $this->getSessionTransferToken(
            LoadFrontendPasswordGrantClient::OAUTH_CLIENT_ID,
            $this->getCustomerUserAccessToken(LoadCustomerUserData::EMAIL, LoadCustomerUserData::PASSWORD)
        );
        $this->client->getCookieJar()->clear();

        $this->disableCustomerUser(LoadCustomerUserData::EMAIL);

        $consumeResponse = $this->consumeSessionTransferToken($sessionTransferToken);

        self::assertResponseStatusCodeEquals($consumeResponse, Response::HTTP_UNAUTHORIZED);
        self::assertNull($consumeResponse->headers->get('Location'));

        $this->client->request(
            'GET',
            $this->getUrl(EmbeddedFragmentController::ROUTE_NAME, ['block' => 'embedded_profile'])
        );
        $response = $this->client->getResponse();
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
        self::assertStringNotContainsString(LoadCustomerUserData::EMAIL, (string) $response->getContent());
    }

    public function testSessionOfCustomerUserDisabledAfterTheConsumeNoLongerAuthenticatesFragment(): void
    {
        $this->loadCustomerUserFixtures();
        $this->openCustomerUserSession(LoadCustomerUserData::EMAIL, LoadCustomerUserData::PASSWORD);
        self::assertStringContainsString(LoadCustomerUserData::EMAIL, $this->getProfileFragmentMarkup());

        $this->disableCustomerUser(LoadCustomerUserData::EMAIL);

        $this->client->request(
            'GET',
            $this->getUrl(EmbeddedFragmentController::ROUTE_NAME, ['block' => 'embedded_profile'])
        );
        $response = $this->client->getResponse();
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
        self::assertStringNotContainsString(LoadCustomerUserData::EMAIL, (string) $response->getContent());
    }

    public function testVisitorTokenIssuedBeforeGuestAccessWasDisabledOpensNoFragmentAccess(): void
    {
        $this->loadFixtures([LoadStorefrontAuthorizationCodeClient::class]);
        $sessionTransferToken = $this->getSessionTransferToken(
            LoadStorefrontAuthorizationCodeClient::OAUTH_CLIENT_ID,
            $this->getVisitorAccessToken()
        );
        $this->client->getCookieJar()->clear();
        $this->setConfigValue('oro_frontend.guest_access_enabled', false);

        $consumeResponse = $this->consumeSessionTransferToken($sessionTransferToken);
        self::assertResponseStatusCodeEquals($consumeResponse, Response::HTTP_SEE_OTHER);

        $this->client->request(
            'GET',
            $this->getUrl(EmbeddedFragmentController::ROUTE_NAME, ['block' => 'embedded_shopping_lists'])
        );

        $response = $this->client->getResponse();
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FOUND);
        self::assertStringEndsWith(
            $this->getUrl('oro_customer_customer_user_security_login'),
            (string) $response->headers->get('Location')
        );
    }

    public function testVisitorTokenCannotBeIssuedWhileGuestAccessIsDisabled(): void
    {
        $this->loadFixtures([LoadStorefrontAuthorizationCodeClient::class]);
        $this->setConfigValue('oro_frontend.guest_access_enabled', false);

        $this->client->request('GET', $this->getVisitorAuthorizeUrl());

        $response = $this->client->getResponse();
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FOUND);
        self::assertStringEndsWith(
            $this->getUrl('oro_customer_customer_user_security_login'),
            (string) $response->headers->get('Location')
        );
    }

    public function testSessionTargetReturnsNoContentThatMustNotBeStored(): void
    {
        $this->client->request('GET', '/embedded/session');

        $response = $this->client->getResponse();
        self::assertEmptyResponseStatusCodeEquals($response, Response::HTTP_NO_CONTENT);
        self::assertTrue($response->headers->hasCacheControlDirective('no-store'));
        self::assertTrue($response->headers->hasCacheControlDirective('private'));
    }

    public function testSessionTargetIsClosedToGuestsWhenGuestAccessIsDisabled(): void
    {
        $this->setConfigValue('oro_frontend.guest_access_enabled', false);

        $this->client->request('GET', $this->getUrl('oro_storefront_embedded_session'));

        $response = $this->client->getResponse();
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FOUND);
        self::assertSame(
            $this->getUrl('oro_customer_customer_user_security_login'),
            $response->headers->get('Location')
        );
    }

    private function loadCustomerUserFixtures(): void
    {
        $this->loadFixtures([LoadCustomerUserData::class, LoadFrontendPasswordGrantClient::class]);
        $this->allowSessionTransferForPasswordGrantClient();
    }

    /**
     * Requests the profile fragment with the current client cookies and returns its markup.
     */
    private function getProfileFragmentMarkup(): string
    {
        $this->client->request(
            'GET',
            $this->getUrl(EmbeddedFragmentController::ROUTE_NAME, ['block' => 'embedded_profile'])
        );

        $content = self::getJsonResponseContent($this->client->getResponse(), Response::HTTP_OK);
        self::assertArrayHasKey('embedded_profile', $content);

        return $content['embedded_profile'];
    }

    private function disableCustomerUser(string $customerUserReference): void
    {
        $entityManager = self::getContainer()->get('doctrine')->getManagerForClass(CustomerUser::class);
        /** @var CustomerUser $customerUser */
        $customerUser = $entityManager->find(
            CustomerUser::class,
            $this->getReference($customerUserReference)->getId()
        );
        $customerUser->setEnabled(false);
        $entityManager->flush();
    }

    private function restoreCookies(array $cookies): void
    {
        $cookieJar = $this->client->getCookieJar();
        $cookieJar->clear();
        array_walk($cookies, $cookieJar->set(...));
    }

    private function findSessionCookie(Response $response): ?Cookie
    {
        return $this->findCookie($response, $this->createStorefrontSession()->getName());
    }

    private function findCookie(Response $response, string $cookieName): ?Cookie
    {
        $cookies = array_filter(
            $response->headers->getCookies(),
            static fn (Cookie $cookie): bool => $cookie->getName() === $cookieName
        );

        return array_shift($cookies);
    }
}
