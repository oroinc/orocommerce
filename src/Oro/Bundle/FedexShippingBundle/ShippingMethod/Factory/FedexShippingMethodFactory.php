<?php

namespace Oro\Bundle\FedexShippingBundle\ShippingMethod\Factory;

// @codingStandardsIgnoreStart
use Oro\Bundle\FedexShippingBundle\Client\RateService\FedexRateServiceBySettingsClientInterface;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Factory\FedexRequestByRateServiceSettingsFactoryInterface;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Settings\Factory\FedexRateServiceRequestSettingsFactoryInterface;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\ShippingMethod\FedexShippingMethod;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\IntegrationBundle\Provider\IntegrationIconProviderInterface;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ShippingBundle\Method\Factory\IntegrationShippingMethodFactoryInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;

// @codingStandardsIgnoreEnd

class FedexShippingMethodFactory implements IntegrationShippingMethodFactoryInterface
{
    /**
     * @var IntegrationIdentifierGeneratorInterface
     */
    private $identifierGenerator;

    /**
     * @var LocalizationHelper
     */
    private $localizationHelper;

    /**
     * @var IntegrationIconProviderInterface
     */
    private $iconProvider;

    /**
     * @var FedexShippingMethodTypeFactoryInterface
     */
    private $typeFactory;

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
        IntegrationIdentifierGeneratorInterface $identifierGenerator,
        LocalizationHelper $localizationHelper,
        IntegrationIconProviderInterface $iconProvider,
        FedexShippingMethodTypeFactoryInterface $typeFactory,
        FedexRateServiceRequestSettingsFactoryInterface $rateServiceRequestSettingsFactory,
        FedexRequestByRateServiceSettingsFactoryInterface $rateServiceRequestFactory,
        FedexRateServiceBySettingsClientInterface $rateServiceClient
    ) {
        $this->identifierGenerator = $identifierGenerator;
        $this->localizationHelper = $localizationHelper;
        $this->iconProvider = $iconProvider;
        $this->typeFactory = $typeFactory;
        $this->rateServiceRequestSettingsFactory = $rateServiceRequestSettingsFactory;
        $this->rateServiceRequestFactory = $rateServiceRequestFactory;
        $this->rateServiceClient = $rateServiceClient;
    }

    /**
     * {@inheritDoc}
     */
    public function create(Channel $channel): FedexShippingMethod
    {
        return new FedexShippingMethod(
            $this->rateServiceRequestSettingsFactory,
            $this->rateServiceRequestFactory,
            $this->rateServiceClient,
            $this->identifierGenerator->generateIdentifier($channel),
            $this->getLabel($channel),
            $this->iconProvider->getIcon($channel),
            $channel->isEnabled(),
            $this->getSettings($channel),
            $this->createTypes($channel)
        );
    }

    private function getLabel(Channel $channel): string
    {
        return (string)$this->localizationHelper->getLocalizedValue(
            $this->getSettings($channel)->getLabels()
        );
    }

    /**
     * @param Channel $channel
     *
     * @return Transport|FedexIntegrationSettings
     */
    private function getSettings(Channel $channel)
    {
        return $channel->getTransport();
    }

    /**
     * @param Channel $channel
     *
     * @return ShippingMethodTypeInterface[]
     */
    private function createTypes(Channel $channel): array
    {
        $types = [];
        foreach ($this->getSettings($channel)->getShippingServices() as $service) {
            $types[] = $this->typeFactory->create($channel, $service);
        }

        return $types;
    }
}
