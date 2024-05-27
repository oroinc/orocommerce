<?php

namespace Oro\Bundle\UPSBundle\Connection\Validator;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;
use Oro\Bundle\UPSBundle\Client\AccessTokenProviderInterface;
use Oro\Bundle\UPSBundle\Client\Factory\Basic\BasicUpsClientFactory;
use Oro\Bundle\UPSBundle\Client\Factory\UpsClientFactoryInterface;
use Oro\Bundle\UPSBundle\Connection\Validator\Request\Factory\UpsConnectionValidatorRequestFactoryInterface;
use Oro\Bundle\UPSBundle\Connection\Validator\Result\Factory\UpsConnectionValidatorResultFactoryInterface;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Psr\Log\LoggerInterface;

/**
 * Basic implementation of UPS Connection Validator
 */
class UpsConnectionValidator implements UpsConnectionValidatorInterface
{
    /**
     * @var UpsConnectionValidatorRequestFactoryInterface
     */
    private $requestFactory;

    /**
     * @var UpsClientFactoryInterface
     */
    private $clientFactory;

    /**
     * @var UpsConnectionValidatorResultFactoryInterface
     */
    private $resultFactory;

    /**
     * @var AccessTokenProviderInterface
     */
    private $accessTokenProvider;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        UpsConnectionValidatorRequestFactoryInterface $requestFactory,
        UpsClientFactoryInterface $clientFactory,
        UpsConnectionValidatorResultFactoryInterface $resultFactory,
        LoggerInterface $logger
    ) {
        $this->requestFactory = $requestFactory;
        $this->clientFactory = $clientFactory;
        $this->resultFactory = $resultFactory;
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public function validateConnectionByUpsSettings(UPSTransport $transport)
    {
        $request = $this->requestFactory->createByTransport($transport);

        $isOAuthConfigured = $this->isOAuthConfigured($transport);
        if ($this->clientFactory instanceof BasicUpsClientFactory) {
            $this->clientFactory->setIsOAuthConfigured($isOAuthConfigured);
        }

        $client = $this->clientFactory
            ->createUpsClient($transport->isUpsTestMode());

        try {
            $headers = [];
            if ($isOAuthConfigured) {
                $token = $this->accessTokenProvider->getAccessToken($transport, $client, true);
                $headers = [
                    'content-type' => 'application/json',
                    'authorization' => 'Bearer ' . $token
                ];
            }

            $response = $client->post($request->getUrl(), $request->getRequestData(), $headers);
        } catch (RestException $e) {
            $this->logger->error($e->getMessage());

            return $this->resultFactory->createExceptionResult($e);
        }

        return $this->resultFactory->createResultByUpsClientResponse($response);
    }

    public function setAccessTokenProvider(AccessTokenProviderInterface $accessTokenProvider): void
    {
        $this->accessTokenProvider = $accessTokenProvider;
    }

    private function isOAuthConfigured(UPSTransport $transport): bool
    {
        return
            !empty($transport->getUpsClientId())
            && !empty($transport->getUpsClientSecret());
    }
}
