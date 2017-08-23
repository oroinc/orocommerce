<?php

namespace Oro\Bundle\FedexShippingBundle\ShippingMethod\Factory;

use Oro\Bundle\FedexShippingBundle\Client\RateService\FedexRateServiceBySettingsClientInterface;
use Oro\Bundle\FedexShippingBundle\Client\Request\Factory\FedexRequestByContextAndSettingsFactoryInterface;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Entity\ShippingService;
use Oro\Bundle\FedexShippingBundle\ShippingMethod\FedexShippingMethodType;
use Oro\Bundle\FedexShippingBundle\ShippingMethod\Identifier\FedexMethodTypeIdentifierGeneratorInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;

class FedexShippingMethodTypeFactory implements FedexShippingMethodTypeFactoryInterface
{
    /**
     * @var FedexMethodTypeIdentifierGeneratorInterface
     */
    private $identifierGenerator;

    /**
     * @var FedexRequestByContextAndSettingsFactoryInterface
     */
    private $rateServiceRequestFactory;

    /**
     * @var FedexRateServiceBySettingsClientInterface
     */
    private $rateServiceClient;

    /**
     * @param FedexMethodTypeIdentifierGeneratorInterface      $identifierGenerator
     * @param FedexRequestByContextAndSettingsFactoryInterface $rateServiceRequestFactory
     * @param FedexRateServiceBySettingsClientInterface        $rateServiceClient
     */
    public function __construct(
        FedexMethodTypeIdentifierGeneratorInterface $identifierGenerator,
        FedexRequestByContextAndSettingsFactoryInterface $rateServiceRequestFactory,
        FedexRateServiceBySettingsClientInterface $rateServiceClient
    ) {
        $this->identifierGenerator = $identifierGenerator;
        $this->rateServiceRequestFactory = $rateServiceRequestFactory;
        $this->rateServiceClient = $rateServiceClient;
    }

    /**
     * {@inheritDoc}
     */
    public function create(Channel $channel, ShippingService $service): ShippingMethodTypeInterface
    {
        return new FedexShippingMethodType(
            $this->rateServiceRequestFactory,
            $this->rateServiceClient,
            $this->identifierGenerator->generate($service),
            $service->getDescription(),
            $this->getSettings($channel)
        );
    }

    /**
     * @param Channel $channel
     *
     * @return FedexIntegrationSettings|Transport
     */
    private function getSettings(Channel $channel)
    {
        return $channel->getTransport();
    }
}
