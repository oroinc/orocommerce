<?php

namespace Oro\Bundle\ApruveBundle\Connection\Validator;

use Oro\Bundle\ApruveBundle\Client\Factory\Settings\ApruveSettingsRestClientFactoryInterface;
use Oro\Bundle\ApruveBundle\Connection\Validator\Request\Factory\ApruveConnectionValidatorRequestFactoryInterface;
use Oro\Bundle\ApruveBundle\Connection\Validator\Result\Factory\ApruveConnectionValidatorResultFactoryInterface;
use Oro\Bundle\ApruveBundle\Entity\ApruveSettings;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;
use Psr\Log\LoggerInterface;

class ApruveConnectionValidator implements ApruveConnectionValidatorInterface
{
    /**
     * @var ApruveConnectionValidatorRequestFactoryInterface
     */
    private $requestFactory;

    /**
     * @var ApruveSettingsRestClientFactoryInterface
     */
    private $clientFactory;

    /**
     * @var ApruveConnectionValidatorResultFactoryInterface
     */
    private $resultFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ApruveSettingsRestClientFactoryInterface         $clientFactory
     * @param ApruveConnectionValidatorRequestFactoryInterface $requestFactory
     * @param ApruveConnectionValidatorResultFactoryInterface  $resultFactory
     * @param LoggerInterface                                  $logger
     */
    public function __construct(
        ApruveSettingsRestClientFactoryInterface $clientFactory,
        ApruveConnectionValidatorRequestFactoryInterface $requestFactory,
        ApruveConnectionValidatorResultFactoryInterface $resultFactory,
        LoggerInterface $logger
    ) {
        $this->clientFactory = $clientFactory;
        $this->requestFactory = $requestFactory;
        $this->resultFactory = $resultFactory;
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public function validateConnectionByApruveSettings(ApruveSettings $settings)
    {
        $request = $this->requestFactory->createBySettings($settings);

        $client = $this->clientFactory->create($settings);

        try {
            $response = $client->execute($request);
        } catch (RestException $e) {
            $this->logger->error($e->getMessage());

            return $this->resultFactory->createExceptionResult($e);
        }

        return $this->resultFactory->createResultByApruveClientResponse($response);
    }
}
