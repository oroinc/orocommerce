<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Functional;

use Oro\Bundle\OAuth2ServerBundle\Entity\Client;
use Oro\Bundle\OAuth2ServerBundle\Tests\Functional\DataFixtures\LoadFrontendPasswordGrantClient;
use Oro\Bundle\StorefrontEmbeddedBundle\Tests\Functional\DataFixtures\LoadStorefrontAuthorizationCodeClient;
use Symfony\Component\HttpFoundation\Response;

/**
 * The OAuth2 steps that an embedded host does to open a storefront session.
 */
trait EmbeddedSessionTransferTestTrait
{
    private function getVisitorAccessToken(): string
    {
        $this->client->request('GET', $this->getUrl('oro_oauth2_server_frontend_authenticate_visitor', [
            'response_type' => 'code',
            'client_id' => LoadStorefrontAuthorizationCodeClient::OAUTH_CLIENT_ID,
            'redirect_uri' => LoadStorefrontAuthorizationCodeClient::OAUTH_CLIENT_REDIRECT_URI
        ]));

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

    /**
     * Opens the session of the customer user in a client without cookies and returns the consume response.
     */
    private function openCustomerUserSession(string $email, string $password): Response
    {
        $this->loadFixtures([LoadFrontendPasswordGrantClient::class]);
        $this->getReference(LoadFrontendPasswordGrantClient::OAUTH_CLIENT_REFERENCE)
            ->setSessionTransferAllowed(true);
        self::getContainer()->get('doctrine')->getManagerForClass(Client::class)->flush();

        $sessionTransferToken = $this->getSessionTransferToken(
            LoadFrontendPasswordGrantClient::OAUTH_CLIENT_ID,
            $this->getAccessToken([
                'grant_type' => 'password',
                'client_id' => LoadFrontendPasswordGrantClient::OAUTH_CLIENT_ID,
                'client_secret' => LoadFrontendPasswordGrantClient::OAUTH_CLIENT_SECRET,
                'username' => $email,
                'password' => $password
            ])
        );
        $this->client->getCookieJar()->clear();

        $consumeResponse = $this->consumeSessionTransferToken($sessionTransferToken);
        self::assertResponseStatusCodeEquals($consumeResponse, Response::HTTP_SEE_OTHER);

        return $consumeResponse;
    }

    private function getSessionTransferToken(string $clientId, string $subjectToken): string
    {
        return $this->getAccessToken([
            'grant_type' => Client::SESSION_TRANSFER,
            'client_id' => $clientId,
            'subject_token' => $subjectToken,
            'route' => 'oro_storefront_embedded_session',
            'route_parameters' => []
        ]);
    }

    private function consumeSessionTransferToken(string $sessionTransferToken): Response
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_oauth2_frontend_session_transfer_consume', ['token' => $sessionTransferToken])
        );

        return $this->client->getResponse();
    }

    private function getAccessToken(array $tokenRequestParameters): string
    {
        $this->client->request('POST', $this->getUrl('oro_oauth2_server_auth_token'), $tokenRequestParameters);

        $response = $this->client->getResponse();
        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);

        return self::jsonToArray($response->getContent())['access_token'];
    }
}
