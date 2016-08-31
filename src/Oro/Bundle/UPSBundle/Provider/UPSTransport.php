<?php

namespace Oro\Bundle\UPSBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Transport\AbstractRestTransport;
use Oro\Bundle\UPSBundle\Model\PriceRequest;

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
     * {@inheritdoc}
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
        return 'oro_ups_transport_setting_form_type';
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
     * @return array
     */
    public function getPrices(PriceRequest $priceRequest)
    {
        $repo = $this->registry
            ->getManagerForClass('Oro\Bundle\IntegrationBundle\Entity\Channel')
            ->getRepository('Oro\Bundle\IntegrationBundle\Entity\Channel');
        /** @var Integration $integration */
        $integration = $repo->findOneBy(['type' => ChannelType::TYPE]);
        if ($integration && $integration->isEnabled()) {
            $transportEntity = $integration->getTransport();
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

                return $this->client->post(static::API_RATES_PREFIX, $priceRequest->toJson())->json();
            }
        }
        return null;
    }
}
