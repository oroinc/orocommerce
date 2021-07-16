<?php

namespace Oro\Bundle\FedexShippingBundle\ShippingMethod\Factory;

// @codingStandardsIgnoreStart
use Oro\Bundle\FedexShippingBundle\Client\RateService\FedexRateServiceBySettingsClientInterface;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Factory\FedexRequestByRateServiceSettingsFactoryInterface;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Settings\Factory\FedexRateServiceRequestSettingsFactoryInterface;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Entity\FedexShippingService;
use Oro\Bundle\FedexShippingBundle\ShippingMethod\FedexShippingMethodType;
use Oro\Bundle\FedexShippingBundle\ShippingMethod\Identifier\FedexMethodTypeIdentifierGeneratorInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;

// @codingStandardsIgnoreEnd

class FedexShippingMethodTypeFactory implements FedexShippingMethodTypeFactoryInterface
{
    /**
     * @var FedexMethodTypeIdentifierGeneratorInterface
     */
    private $identifierGenerator;

    /**
     * @var FedexRateServiceRequestSettingsFactoryInterface
     */
    private $rateServiceRequestSettingsFactory;

    /**
     * @var FedexRequestByRateServiceSettingsFactoryInterface
     */
    private $rateServiceRequestFactory;

    /**
     * @var FedexRateServiceBySettingsClientInterface
     */
    private $rateServiceClient;

    public function __construct(
        FedexMethodTypeIdentifierGeneratorInterface $identifierGenerator,
        FedexRateServiceRequestSettingsFactoryInterface $rateServiceRequestSettingsFactory,
        FedexRequestByRateServiceSettingsFactoryInterface $rateServiceRequestFactory,
        FedexRateServiceBySettingsClientInterface $rateServiceClient
    ) {
        $this->identifierGenerator = $identifierGenerator;
        $this->rateServiceRequestSettingsFactory = $rateServiceRequestSettingsFactory;
        $this->rateServiceRequestFactory = $rateServiceRequestFactory;
        $this->rateServiceClient = $rateServiceClient;
    }

    /**
     * {@inheritDoc}
     */
    public function create(Channel $channel, FedexShippingService $service): ShippingMethodTypeInterface
    {
        return new FedexShippingMethodType(
            $this->rateServiceRequestSettingsFactory,
            $this->rateServiceRequestFactory,
            $this->rateServiceClient,
            $this->identifierGenerator->generate($service),
            $service,
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
