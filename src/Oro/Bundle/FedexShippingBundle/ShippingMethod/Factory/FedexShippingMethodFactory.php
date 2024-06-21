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

// @codingStandardsIgnoreEnd

/**
 * The factory to create FedEx shipping method.
 */
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
     * @var FedexRequestByRateServiceSettingsFactoryInterface
     */
    private $rateServiceRequestSoapFactory;

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
        FedexRequestByRateServiceSettingsFactoryInterface $rateServiceRequestSoapFactory,
        FedexRateServiceBySettingsClientInterface $rateServiceClient
    ) {
        $this->identifierGenerator = $identifierGenerator;
        $this->localizationHelper = $localizationHelper;
        $this->iconProvider = $iconProvider;
        $this->typeFactory = $typeFactory;
        $this->rateServiceRequestSettingsFactory = $rateServiceRequestSettingsFactory;
        $this->rateServiceRequestFactory = $rateServiceRequestFactory;
        $this->rateServiceRequestSoapFactory = $rateServiceRequestSoapFactory;
        $this->rateServiceClient = $rateServiceClient;
    }

    /**
     * {@inheritDoc}
     */
    public function create(Channel $channel)
    {
        /** @var FedexIntegrationSettings $transport */
        $transport = $channel->getTransport();
        $types = [];
        $shippingServices = $transport->getShippingServices();
        foreach ($shippingServices as $shippingService) {
            $types[] = $this->typeFactory->create($channel, $shippingService);
        }

        $requestFactory = $this->rateServiceRequestSoapFactory;
        if ($transport->getClientSecret() && $transport->getClientId()) {
            $requestFactory = $this->rateServiceRequestFactory;
        }

        return new FedexShippingMethod(
            $this->rateServiceRequestSettingsFactory,
            $requestFactory,
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
