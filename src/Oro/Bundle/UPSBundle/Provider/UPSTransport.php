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

use Symfony\Component\HttpFoundation\ParameterBag;

class UPSTransport extends AbstractRestTransport
{
    const API_RATES_PREFIX = 'Rate';

    /**
     * @var  ManagerRegistry
     */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
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
     * @param PriceRequest $priceRequest
     * @param Transport $transportEntity
     * @throws RestException
     * @throws InvalidConfigurationException
     * @throws \InvalidArgumentException
     * @return PriceResponse|null
     */
    public function getPrices(PriceRequest $priceRequest, Transport $transportEntity)
    {
        if ($transportEntity) {
            $this->client = $this->createRestClient($transportEntity);

            $parameterBag = $transportEntity->getSettingsBag();
            $priceRequest->setSecurity(
                $parameterBag->get('api_user'),
                $parameterBag->get('api_password'),
                $parameterBag->get('api_key')
            );
            $priceRequest
                ->setShipperName($parameterBag->get('shipping_account_name'))
                ->setShipperNumber($parameterBag->get('shipping_account_number'));

            $data = $this->client->post(static::API_RATES_PREFIX, $priceRequest->toJson())->json();
            $priceResponse = new PriceResponse();
            if (!is_array($data)) {
                return null;
            }
            $priceResponse->parse($data);

            return $priceResponse;
        }

        return null;
    }
}
