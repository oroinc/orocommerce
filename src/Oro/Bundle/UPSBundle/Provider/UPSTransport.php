<?php

namespace Oro\Bundle\UPSBundle\Provider;

use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Transport\AbstractRestTransport;
use Oro\Bundle\UPSBundle\Client\Url\Provider\UpsClientUrlProviderInterface;
use Oro\Bundle\UPSBundle\Form\Type\UPSTransportSettingsType;
use Oro\Bundle\UPSBundle\Model\PriceRequest;
use Oro\Bundle\UPSBundle\Model\PriceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class UPSTransport extends AbstractRestTransport
{
    const API_RATES_PREFIX = 'Rate';

    /**
     * @var UpsClientUrlProviderInterface
     */
    private $upsClientUrlProvider;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(UpsClientUrlProviderInterface $upsClientUrlProvider, LoggerInterface $logger)
    {
        $this->upsClientUrlProvider = $upsClientUrlProvider;
        $this->logger = $logger;
    }

    /**
     * @param ParameterBag $parameterBag
     *
     * @throws \InvalidArgumentException
     * @return string
     */
    protected function getClientBaseUrl(ParameterBag $parameterBag)
    {
        return $this->upsClientUrlProvider->getUpsUrl($parameterBag->get('test_mode'));
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
     * @param PriceRequest $priceRequest
     * @param Transport $transportEntity
     * @throws RestException
     * @throws InvalidConfigurationException
     * @throws \InvalidArgumentException
     * @return PriceResponse|null
     */
    public function getPriceResponse(PriceRequest $priceRequest, Transport $transportEntity)
    {
        try {
            $this->client = $this->createRestClient($transportEntity);
            $data = $this->client->post(static::API_RATES_PREFIX, $priceRequest->toJson())->json();

            if (!is_array($data)) {
                return null;
            }

            return (new PriceResponse())->parse($data);
        } catch (\LogicException $e) {
            $this->logger->error(
                sprintf('Price request failed for transport #%s. %s', $transportEntity->getId(), $e->getMessage())
            );
        } catch (RestException $restException) {
            $this->logger->error(
                sprintf(
                    'Price REST request failed for transport #%s. %s',
                    $transportEntity->getId(),
                    $restException->getMessage()
                )
            );
        }

        return null;
    }
}
