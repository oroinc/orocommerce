<?php

namespace Oro\Bundle\FedexShippingBundle\ShippingMethod\Factory;

// @codingStandardsIgnoreStart
use Oro\Bundle\FedexShippingBundle\Client\RateService\FedexRateServiceBySettingsClientInterface;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Factory\FedexRequestByRateServiceSettingsFactoryInterface;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Settings\Factory\FedexRateServiceRequestSettingsFactoryInterface;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\ShippingMethod\FedexShippingMethod;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\IntegrationBundle\Provider\IntegrationIconProviderInterface;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ShippingBundle\Method\Factory\IntegrationShippingMethodFactoryInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;

// @codingStandardsIgnoreEnd

/**
 * The factory to create FedEx shipping method.
 */
class FedexShippingMethodFactory implements IntegrationShippingMethodFactoryInterface
{
    private IntegrationIdentifierGeneratorInterface $identifierGenerator;
    private LocalizationHelper $localizationHelper;
    private IntegrationIconProviderInterface $iconProvider;
    private FedexShippingMethodTypeFactoryInterface $typeFactory;
    private FedexRateServiceRequestSettingsFactoryInterface $rateServiceRequestSettingsFactory;
    private FedexRequestByRateServiceSettingsFactoryInterface $rateServiceRequestFactory;
    private FedexRateServiceBySettingsClientInterface $rateServiceClient;

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
    public function create(Channel $channel): ShippingMethodInterface
    {
        /** @var FedexIntegrationSettings $transport */
        $transport = $channel->getTransport();
        $types = [];
        $shippingServices = $transport->getShippingServices();
        foreach ($shippingServices as $shippingService) {
            $types[] = $this->typeFactory->create($channel, $shippingService);
        }

        return new FedexShippingMethod(
            $this->rateServiceRequestSettingsFactory,
            $this->rateServiceRequestFactory,
            $this->rateServiceClient,
            $this->identifierGenerator->generateIdentifier($channel),
            (string)$this->localizationHelper->getLocalizedValue($transport->getLabels()),
            $this->iconProvider->getIcon($channel),
            $channel->isEnabled(),
            $transport,
            $types
        );
    }
}
