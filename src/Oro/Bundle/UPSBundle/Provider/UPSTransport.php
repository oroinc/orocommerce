<?php

namespace Oro\Bundle\UPSBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Transport\AbstractRestTransport;
use Oro\Bundle\UPSBundle\Form\Type\UPSTransportSettingsType;
use Oro\Bundle\UPSBundle\Model\PriceRequest;
use Oro\Bundle\UPSBundle\Model\PriceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class UPSTransport extends AbstractRestTransport
{
    const API_RATES_PREFIX = 'Rate';

    /**
     * @var  ManagerRegistry
     */
    protected $registry;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param ManagerRegistry $registry
     * @param LoggerInterface $logger
     */
    public function __construct(ManagerRegistry $registry, LoggerInterface $logger)
    {
        $this->registry = $registry;
        $this->logger = $logger;
    }

    /**
     * @param ParameterBag $parameterBag
     * @throws \InvalidArgumentException
     * @return string
     */
    protected function getClientBaseUrl(ParameterBag $parameterBag)
    {
        return rtrim($parameterBag->get('base_url'), '/') . '/';
    }

    /**
     * {@inheritdoc}
     */
    protected function getClientOptions(ParameterBag $parameterBag)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'oro.ups.transport.label';
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsFormType()
    {
        return UPSTransportSettingsType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsEntityFQCN()
    {
        return 'Oro\Bundle\UPSBundle\Entity\UPSTransport';
    }

    /**
     * @return PriceResponse
     */
    private function createPriceResponse()
    {
        return new PriceResponse();
    }

    /**
     * @param PriceRequest $priceRequest
     * @param Transport $transportEntity
     * @throws RestException
     * @throws InvalidConfigurationException
     * @throws \InvalidArgumentException
     * @return PriceResponse|null
     */
    public function getPrices(PriceRequest $priceRequest, Transport $transportEntity)
    {
        if (!$transportEntity) {
            return null;
        }

        $priceResponse = null;

        try {
            $this->client = $this->createRestClient($transportEntity);
            $data = $this->client->post(static::API_RATES_PREFIX, $priceRequest->toJson())->json();

            if (!is_array($data)) {
                return null;
            }

            $priceResponse = $this->createPriceResponse()->parse($data);
        } catch (\LogicException $e) {
            $this->logger->error(
                sprintf('Price request failed for transport #%s. %s', $transportEntity->getId(), $e->getMessage())
            );
        } catch (RestException $restException) {
            $this->logger->error(
                sprintf('Price REST request failed for transport #%s. %s',
                    $transportEntity->getId(),
                    $restException->getMessage()
                )
            );
        }

        return $priceResponse;
    }
}
