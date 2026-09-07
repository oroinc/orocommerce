<?php

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\CustomerBundle\Security\AnonymousCustomerUserAuthenticator;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\OAuth2ServerBundle\Entity\Manager\ClientManager;
use Oro\Bundle\OAuth2ServerBundle\Security\VisitorAccessTokenParser;
use Oro\Bundle\OAuth2ServerBundle\Tests\Functional\DataFixtures\LoadFrontendClientCredentialsClient;
use Oro\Bundle\OAuth2ServerBundle\Tests\Functional\DataFixtures\LoadFrontendPasswordGrantClient;
use Oro\Bundle\OAuth2ServerBundle\Tests\Functional\DataFixtures\LoadPasswordGrantClient;
use Oro\Bundle\StorefrontEmbeddedBundle\Tests\Functional\DataFixtures\LoadStorefrontAuthorizationCodeClient;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 */
class EmbeddedSessionControllerTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadUser::class,
            LoadCustomerUserData::class,
            LoadPasswordGrantClient::class,
            LoadFrontendPasswordGrantClient::class,
            LoadFrontendClientCredentialsClient::class,
            LoadStorefrontAuthorizationCodeClient::class
        ]);
    }

    public function testReturnsUnauthorizedWhenAuthorizationHeaderIsMissing(): void
    {
        $this->sendBootstrapRequest([]);

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_UNAUTHORIZED);
    }

    public function testReturnsUnauthorizedForMalformedBearerToken(): void
    {
        $this->sendBootstrapRequest($this->getBearerAuthorizationHeader('not-an-access-token'));

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_UNAUTHORIZED);
    }

    public function testReturnsUnauthorizedForBackOfficeAccessToken(): void
    {
        $accessToken = $this->getBackOfficeUserAccessToken();

        $this->sendBootstrapRequest($this->getBearerAuthorizationHeader($accessToken));

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_UNAUTHORIZED);
    }

    public function testReturnsUnauthorizedForFrontendAccessTokenNotBoundToCustomerUser(): void
    {
        $accessToken = $this->getFrontendClientCredentialsAccessToken();

        $this->sendBootstrapRequest($this->getBearerAuthorizationHeader($accessToken));

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_UNAUTHORIZED);
    }

    public function testReturnsUnauthorizedForAccessTokenOfDeactivatedOAuthClient(): void
    {
        $accessToken = $this->getFrontendCustomerUserAccessToken();

        $oauthClient = $this->getReference(LoadFrontendPasswordGrantClient::OAUTH_CLIENT_REFERENCE);
        $this->getClientManager()->deactivateClient($oauthClient);

        $this->sendBootstrapRequest($this->getBearerAuthorizationHeader($accessToken));

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_UNAUTHORIZED);
    }

    public function testOpensStorefrontSessionForFrontendCustomerUserAccessToken(): void
    {
        $accessToken = $this->getFrontendCustomerUserAccessToken();

        $this->sendBootstrapRequest($this->getBearerAuthorizationHeader($accessToken));

        $response = $this->client->getResponse();
        self::assertEmptyResponseStatusCodeEquals($response, Response::HTTP_NO_CONTENT);

        $sessionCookie = $this->findSessionCookie($response);
        self::assertNotNull($sessionCookie, 'The storefront session cookie must be returned to the host.');
        self::assertNotEmpty($sessionCookie->getValue());
    }

    public function testOpenedSessionAuthenticatesFollowUpStorefrontRequestWithoutAccessToken(): void
    {
        $accessToken = $this->getFrontendCustomerUserAccessToken();
        $this->sendBootstrapRequest($this->getBearerAuthorizationHeader($accessToken));
        self::assertEmptyResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_NO_CONTENT);

        $this->client->request('GET', $this->getUrl('oro_customer_frontend_customer_user_profile'));

        $response = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($response, Response::HTTP_OK);
        self::assertStringContainsString(LoadCustomerUserData::EMAIL, $response->getContent());
    }

    public function testAnswersVisitorAccessTokenWithTheCustomerVisitorCookieOfThatToken(): void
    {
        $accessToken = $this->getVisitorAccessToken();

        $this->sendBootstrapRequest($this->getBearerAuthorizationHeader($accessToken));

        $response = $this->client->getResponse();
        self::assertEmptyResponseStatusCodeEquals($response, Response::HTTP_NO_CONTENT);

        self::assertEquals(
            $this->getVisitorSessionId($accessToken),
            $this->getVisitorSessionIdFromCookie($response)
        );
    }

    public function testVisitorCookieAuthenticatesFollowUpStorefrontRequestAsTheSameVisitor(): void
    {
        $accessToken = $this->getVisitorAccessToken();
        $this->sendBootstrapRequest($this->getBearerAuthorizationHeader($accessToken));
        self::assertEmptyResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_NO_CONTENT);

        $this->client->request('GET', $this->getUrl('oro_frontend_root'));

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_OK);
        $token = self::getContainer()->get('security.token_storage')->getToken();
        self::assertInstanceOf(AnonymousCustomerUserToken::class, $token);
        self::assertEquals($this->getVisitorSessionId($accessToken), $token->getVisitor()->getSessionId());
    }

    private function sendBootstrapRequest(array $server): void
    {
        $this->client->request(
            'POST',
            $this->getUrl('oro_storefront_embedded_session'),
            [],
            [],
            $server
        );
    }

    private function getBearerAuthorizationHeader(string $accessToken): array
    {
        return ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $accessToken)];
    }

    private function getFrontendCustomerUserAccessToken(): string
    {
        return $this->getAccessToken([
            'grant_type' => 'password',
            'client_id' => LoadFrontendPasswordGrantClient::OAUTH_CLIENT_ID,
            'client_secret' => LoadFrontendPasswordGrantClient::OAUTH_CLIENT_SECRET,
            'username' => LoadCustomerUserData::EMAIL,
            'password' => LoadCustomerUserData::PASSWORD
        ]);
    }

    private function getVisitorAccessToken(): string
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_oauth2_server_frontend_authenticate_visitor', [
                'response_type' => 'code',
                'client_id' => LoadStorefrontAuthorizationCodeClient::OAUTH_CLIENT_ID,
                'redirect_uri' => LoadStorefrontAuthorizationCodeClient::OAUTH_CLIENT_REDIRECT_URI
            ])
        );

        $response = $this->client->getResponse();
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FOUND);
        parse_str(parse_url($response->headers->get('location'), PHP_URL_QUERY), $parameters);

        return $this->getAccessToken([
            'grant_type' => 'authorization_code',
            'client_id' => LoadStorefrontAuthorizationCodeClient::OAUTH_CLIENT_ID,
            'client_secret' => LoadStorefrontAuthorizationCodeClient::OAUTH_CLIENT_SECRET,
            'redirect_uri' => LoadStorefrontAuthorizationCodeClient::OAUTH_CLIENT_REDIRECT_URI,
            'code' => $parameters['code']
        ]);
    }

    private function getVisitorSessionId(string $accessToken): string
    {
        /** @var VisitorAccessTokenParser $visitorAccessTokenParser */
        $visitorAccessTokenParser = self::getContainer()->get('oro_oauth2_server.visitor_access_token_parser');

        return $visitorAccessTokenParser->getVisitorSessionId($accessToken);
    }

    private function getVisitorSessionIdFromCookie(Response $response): ?string
    {
        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->getName() === AnonymousCustomerUserAuthenticator::COOKIE_NAME) {
                return json_decode(base64_decode($cookie->getValue()), true);
            }
        }

        return null;
    }

    private function getFrontendClientCredentialsAccessToken(): string
    {
        return $this->getAccessToken([
            'grant_type' => 'client_credentials',
            'client_id' => LoadFrontendClientCredentialsClient::OAUTH_CLIENT_ID,
            'client_secret' => LoadFrontendClientCredentialsClient::OAUTH_CLIENT_SECRET
        ]);
    }

    private function getBackOfficeUserAccessToken(): string
    {
        $userName = $this->getReference(LoadUser::USER)->getUserIdentifier();

        return $this->getAccessToken([
            'grant_type' => 'password',
            'client_id' => LoadPasswordGrantClient::OAUTH_CLIENT_ID,
            'client_secret' => LoadPasswordGrantClient::OAUTH_CLIENT_SECRET,
            'username' => $userName,
            'password' => $userName
        ]);
    }

    private function getAccessToken(array $tokenRequestParameters): string
    {
        $this->client->request('POST', $this->getUrl('oro_oauth2_server_auth_token'), $tokenRequestParameters);

        $response = $this->client->getResponse();
        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);

        return self::jsonToArray($response->getContent())['access_token'];
    }

    private function findSessionCookie(Response $response): ?Cookie
    {
        $sessionCookieName = self::getContainer()->get('session.factory')->createSession()->getName();
        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->getName() === $sessionCookieName) {
                return $cookie;
            }
        }

        return null;
    }

    private function getClientManager(): ClientManager
    {
        return self::getContainer()->get('oro_oauth2_server.client_manager');
    }
}
