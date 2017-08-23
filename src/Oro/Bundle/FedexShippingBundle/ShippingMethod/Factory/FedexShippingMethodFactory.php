<?php

namespace Oro\Bundle\FedexShippingBundle\ShippingMethod\Factory;

use Oro\Bundle\FedexShippingBundle\Client\RateService\FedexRateServiceBySettingsClientInterface;
use Oro\Bundle\FedexShippingBundle\Client\Request\Factory\FedexRequestByContextAndSettingsFactoryInterface;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\ShippingMethod\FedexShippingMethod;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\IntegrationBundle\Provider\IntegrationIconProviderInterface;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ShippingBundle\Method\Factory\IntegrationShippingMethodFactoryInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;

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
     * @var FedexRequestByContextAndSettingsFactoryInterface
     */
    private $rateServiceRequestFactory;

    /**
     * @var FedexRateServiceBySettingsClientInterface
     */
    private $rateServiceClient;

    /**
     * @param IntegrationIdentifierGeneratorInterface          $identifierGenerator
     * @param LocalizationHelper                               $localizationHelper
     * @param IntegrationIconProviderInterface                 $iconProvider
     * @param FedexShippingMethodTypeFactoryInterface          $typeFactory
     * @param FedexRequestByContextAndSettingsFactoryInterface $rateServiceRequestFactory
     * @param FedexRateServiceBySettingsClientInterface        $rateServiceClient
     */
    public function __construct(
        IntegrationIdentifierGeneratorInterface $identifierGenerator,
        LocalizationHelper $localizationHelper,
        IntegrationIconProviderInterface $iconProvider,
        FedexShippingMethodTypeFactoryInterface $typeFactory,
        FedexRequestByContextAndSettingsFactoryInterface $rateServiceRequestFactory,
        FedexRateServiceBySettingsClientInterface $rateServiceClient
    ) {
        $this->identifierGenerator = $identifierGenerator;
        $this->localizationHelper = $localizationHelper;
        $this->iconProvider = $iconProvider;
        $this->typeFactory = $typeFactory;
        $this->rateServiceRequestFactory = $rateServiceRequestFactory;
        $this->rateServiceClient = $rateServiceClient;
    }

    /**
     * {@inheritDoc}
     */
    public function create(Channel $channel): FedexShippingMethod
    {
        return new FedexShippingMethod(
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

    /**
     * @param Channel $channel
     *
     * @return string
     */
    private function getLabel(Channel $channel): string
    {
        return (string) $this->localizationHelper->getLocalizedValue(
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
