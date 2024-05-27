<?php

namespace Oro\Bundle\UPSBundle\Provider;

use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Transport\AbstractRestTransport;
use Oro\Bundle\UPSBundle\Client\AccessTokenProviderInterface;
use Oro\Bundle\UPSBundle\Client\Url\Provider\UpsClientUrlProviderInterface;
use Oro\Bundle\UPSBundle\Form\Type\UPSTransportSettingsType;
use Oro\Bundle\UPSBundle\Model\PriceRequest;
use Oro\Bundle\UPSBundle\Model\PriceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * UPS REST transport that uses REST client factory to create REST client
 */
class UPSTransport extends AbstractRestTransport
{
    private const API_RATES_PREFIX = 'Rate';

    /**
     * @internal
     * https://developer.ups.com/api/reference?loc=en_US#operation/Shop
     * Rate - is the only valid request option for UPS Ground Freight Pricing requests.
     * Shop - The server validates the shipment, and returns rates for all UPS products
     *        from the ShipFrom to the ShipTo addresses.
     */
    private const API_RATES_PREFIX_OAUTH = '/api/rating/v2403/Shop';

    public function __construct(
        private UpsClientUrlProviderInterface $upsClientUrlProvider,
        private UpsClientUrlProviderInterface $upsClientOAuthUrlProvider,
        private AccessTokenProviderInterface $accessTokenProvider,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param ParameterBag $parameterBag
     *
     * @throws \InvalidArgumentException
     * @return string
     */
    protected function getClientBaseUrl(ParameterBag $parameterBag)
    {
        if ($parameterBag->get('client_id')
            && $parameterBag->get('client_secret')
        ) {
            return $this->upsClientOAuthUrlProvider->getUpsUrl($parameterBag->get('test_mode'));
        }

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
    public function getPriceResponse(PriceRequest $priceRequest, Transport $transportEntity): ?PriceResponse
    {
        try {
            $this->client = $this->createRestClient($transportEntity);

            $resource = static::API_RATES_PREFIX;
            if ($transportEntity instanceof \Oro\Bundle\UPSBundle\Entity\UPSTransport
                && !empty($transportEntity->getUpsClientId())
                && !empty($transportEntity->getUpsClientSecret())
            ) {
                $token = $this->accessTokenProvider->getAccessToken($transportEntity, $this->client);
                $resource = static::API_RATES_PREFIX_OAUTH;
                $headers = [
                    'content-type' => 'application/json',
                    'authorization' => 'Bearer ' . $token
                ];
            }

            $data = $this->client
                ->post($resource, $priceRequest->toJson(), $headers ?? [])
                ->json();

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
