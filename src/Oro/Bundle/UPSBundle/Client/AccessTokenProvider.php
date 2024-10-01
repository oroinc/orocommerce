<?php

namespace Oro\Bundle\UPSBundle\Client;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;

/**
 * OAuth access token provider.
 */
class AccessTokenProvider implements AccessTokenProviderInterface
{
    /**
     * https://developer.ups.com/api/reference?loc=en_US#tag/OAuthClientCredentials_other
     */
    private const REQUEST_URI = '/security/v1/oauth/token';
    private const MAX_RETRY_ATTEMPTS = 3;

    public function __construct(
        private SymmetricCrypterInterface $crypter,
        private ManagerRegistry $doctrine
    ) {
    }

    #[\Override]
    public function getAccessToken(
        UPSTransport $transport,
        RestClientInterface $client,
        bool $isCheckMode = false
    ): ?string {
        $tokenExpiresAt = $transport->getUpsAccessTokenExpiresAt();
        if ($isCheckMode
            || null === $tokenExpiresAt
            || $tokenExpiresAt < new \DateTime('now', new \DateTimeZone('UTC'))
        ) {
            $response = $this->doAccessTokenHttpRequest(
                $client,
                [
                    'grant_type' => 'client_credentials',
                ],
                [
                    'Authorization' => 'Basic '
                        . base64_encode(
                            $transport->getUpsClientId()
                            . ':'
                            . $this->crypter->decryptData($transport->getUpsClientSecret())
                        ),
                    'x-merchant-id' => $transport->getUpsShippingAccountNumber()
                ]
            );

            $transport->setUpsAccessToken($response['access_token']);
            $transport->setUpsAccessTokenExpiresAt(
                new \DateTime('+' . ($response['expires_in'] - 5) . ' seconds', new \DateTimeZone('UTC'))
            );

            $em = $this->doctrine->getManagerForClass($transport::class);
            $em->persist($transport);
            $em->flush();
        }

        return $transport->getUpsAccessToken();
    }

    private function doAccessTokenHttpRequest(RestClientInterface $client, array $parameters, array $headers): array
    {
        $attemptNumber = 0;
        do {
            $responseObject = $this->doHttpRequest($client, $parameters, $headers);
            $response = $responseObject->json();
            $attemptNumber++;
        } while ($attemptNumber <= self::MAX_RETRY_ATTEMPTS && empty($response['access_token']));

        if (empty($response['access_token'])) {
            throw RestException::createFromResponse($responseObject, 'Access token was not get.');
        }

        return $response;
    }

    private function doHttpRequest(
        RestClientInterface $client,
        array $parameters,
        array $headers
    ): RestResponseInterface {
        $content = http_build_query($parameters);
        $headers = array_merge(
            $headers,
            [
                'Content-length' => \strlen($content),
                'content-type'   => 'application/x-www-form-urlencoded',
                'user-agent'     => 'oro-oauth',
            ]
        );

        return $client->post(
            self::REQUEST_URI,
            $content,
            $headers
        );
    }
}
