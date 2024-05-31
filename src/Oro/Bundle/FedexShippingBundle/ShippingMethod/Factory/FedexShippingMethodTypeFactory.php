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

/**
 * Factory that creates FedexShippingMethodType instance.
 */
class FedexShippingMethodTypeFactory implements FedexShippingMethodTypeFactoryInterface
{
    private FedexMethodTypeIdentifierGeneratorInterface $identifierGenerator;
    private FedexRateServiceRequestSettingsFactoryInterface $rateServiceRequestSettingsFactory;
    private FedexRequestByRateServiceSettingsFactoryInterface $rateServiceRequestFactory;
    private FedexRequestByRateServiceSettingsFactoryInterface $rateServiceRequestSoapFactory;
    private FedexRateServiceBySettingsClientInterface $rateServiceClient;

    public function __construct(
        FedexMethodTypeIdentifierGeneratorInterface $identifierGenerator,
        FedexRateServiceRequestSettingsFactoryInterface $rateServiceRequestSettingsFactory,
        FedexRequestByRateServiceSettingsFactoryInterface $rateServiceRequestFactory,
        FedexRequestByRateServiceSettingsFactoryInterface $rateServiceRequestSoapFactory,
        FedexRateServiceBySettingsClientInterface $rateServiceClient
    ) {
        $this->identifierGenerator = $identifierGenerator;
        $this->rateServiceRequestSettingsFactory = $rateServiceRequestSettingsFactory;
        $this->rateServiceRequestFactory = $rateServiceRequestFactory;
        $this->rateServiceRequestSoapFactory = $rateServiceRequestSoapFactory;
        $this->rateServiceClient = $rateServiceClient;
    }

    #[\Override]
    public function create(Channel $channel, FedexShippingService $service): ShippingMethodTypeInterface
    {
        $settings = $this->getSettings($channel);
        $requestFactory = $this->rateServiceRequestSoapFactory;
        if ($settings->getClientSecret() && $settings->getClientId()) {
            $requestFactory = $this->rateServiceRequestFactory;
        }

        return new FedexShippingMethodType(
            $this->rateServiceRequestSettingsFactory,
            $requestFactory,
            $this->rateServiceClient,
            $this->identifierGenerator->generate($service),
            $service,
            $settings
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
