<?php

namespace Oro\Bundle\FedexShippingBundle\Client\RateService;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientFactoryInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;

/**
 * OAuth access token provider.
 */
class AccessTokenProvider implements AccessTokenProviderInterface
{
    private const REQUEST_URI = '/oauth/token';
    private const MAX_RETRY_ATTEMPTS = 3;

    private RestClientFactoryInterface $restClientFactory;
    private SymmetricCrypterInterface $crypter;
    private ManagerRegistry $doctrine;

    public function __construct(
        RestClientFactoryInterface $restClientFactory,
        SymmetricCrypterInterface $crypter,
        ManagerRegistry $doctrine
    ) {
        $this->restClientFactory = $restClientFactory;
        $this->crypter = $crypter;
        $this->doctrine = $doctrine;
    }

    public function getAccessToken(
        FedexIntegrationSettings $settings,
        string $baseUrl,
        bool $isCheckMode = false
    ): ?string {
        $client = $this->restClientFactory->createRestClient($baseUrl, []);
        $tokenExpiresAt = $settings->getAccessTokenExpiresAt();
        if ($isCheckMode
            || null === $tokenExpiresAt
            || $tokenExpiresAt < new \DateTime('now', new \DateTimeZone('UTC'))
        ) {
            $response = $this->doAccessTokenHttpRequest(
                $client,
                [
                    'grant_type' => 'client_credentials',
                    'client_id' => $settings->getClientId(),
                    'client_secret' => $this->crypter->decryptData($settings->getClientSecret())
                ]
            );

            $settings->setAccessToken($response['access_token']);
            $settings->setAccessTokenExpiresAt(
                new \DateTime('+' . ($response['expires_in'] - 5) . ' seconds', new \DateTimeZone('UTC'))
            );

            if (!$isCheckMode) {
                $em = $this->doctrine->getManagerForClass($settings::class);
                $em->persist($settings);
                $em->flush();
            }
        }

        return $settings->getAccessToken();
    }

    private function doAccessTokenHttpRequest(RestClientInterface $client, array $parameters): array
    {
        $attemptNumber = 0;
        do {
            $responseObject = $this->doHttpRequest($client, $parameters);
            $response = $responseObject->json();
            $attemptNumber++;
        } while ($attemptNumber <= self::MAX_RETRY_ATTEMPTS && empty($response['access_token']));

        if (empty($response['access_token'])) {
            throw RestException::createFromResponse($responseObject, 'Access token was not get.');
        }

        return $response;
    }

    private function doHttpRequest(RestClientInterface $client, array $parameters): RestResponseInterface
    {
        $content = http_build_query($parameters);
        return $client->post(
            self::REQUEST_URI,
            $content,
            [
                'Content-length' => \strlen($content),
                'content-type'   => 'application/x-www-form-urlencoded',
                'user-agent'     => 'oro-oauth'
            ]
        );
    }
}
