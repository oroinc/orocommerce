<?php

namespace Oro\Bundle\UPSBundle\Method\Factory;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\IntegrationBundle\Provider\IntegrationIconProviderInterface;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ShippingBundle\Method\Factory\IntegrationShippingMethodFactoryInterface;
use Oro\Bundle\UPSBundle\Cache\ShippingPriceCache;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Oro\Bundle\UPSBundle\Entity\UPSTransport as UPSSettings;
use Oro\Bundle\UPSBundle\Factory\PriceRequestFactory;
use Oro\Bundle\UPSBundle\Method\UPSShippingMethod;
use Oro\Bundle\UPSBundle\Provider\UPSTransport;

class UPSShippingMethodFactory implements IntegrationShippingMethodFactoryInterface
{
    /**
     * @var UPSTransport
     */
    private $transport;

    /**
     * @var PriceRequestFactory
     */
    private $priceRequestFactory;

    /**
     * @var LocalizationHelper
     */
    private $localizationHelper;

    /**
     * @var ShippingPriceCache
     */
    private $shippingPriceCache;

    /**
     * @var IntegrationIdentifierGeneratorInterface
     */
    private $integrationIdentifierGenerator;

    /**
     * @var UPSShippingMethodTypeFactoryInterface
     */
    private $methodTypeFactory;

    /**
     * @var IntegrationIconProviderInterface
     */
    private $integrationIconProvider;

    public function __construct(
        UPSTransport $transport,
        PriceRequestFactory $priceRequestFactory,
        LocalizationHelper $localizationHelper,
        IntegrationIconProviderInterface $integrationIconProvider,
        ShippingPriceCache $shippingPriceCache,
        IntegrationIdentifierGeneratorInterface $integrationIdentifierGenerator,
        UPSShippingMethodTypeFactoryInterface $methodTypeFactory
    ) {
        $this->transport = $transport;
        $this->priceRequestFactory = $priceRequestFactory;
        $this->localizationHelper = $localizationHelper;
        $this->shippingPriceCache = $shippingPriceCache;
        $this->integrationIdentifierGenerator = $integrationIdentifierGenerator;
        $this->methodTypeFactory = $methodTypeFactory;
        $this->integrationIconProvider = $integrationIconProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function create(Channel $channel)
    {
        return new UPSShippingMethod(
            $this->getIdentifier($channel),
            $this->getLabel($channel),
            $this->getIcon($channel),
            $this->createTypes($channel),
            $this->getSettings($channel),
            $this->transport,
            $this->priceRequestFactory,
            $this->shippingPriceCache,
            $channel->isEnabled()
        );
    }

    /**
     * @param Channel $channel
     * @return string
     */
    private function getIdentifier(Channel $channel)
    {
        return $this->integrationIdentifierGenerator->generateIdentifier($channel);
    }

    /**
     * @param Channel $channel
     * @return string
     */
    private function getLabel(Channel $channel)
    {
        $settings = $this->getSettings($channel);

        return (string)$this->localizationHelper->getLocalizedValue($settings->getLabels());
    }

    /**
     * @param Channel $channel
     * @return \Oro\Bundle\IntegrationBundle\Entity\Transport|UPSSettings
     */
    private function getSettings(Channel $channel)
    {
        return $channel->getTransport();
    }

    /**
     * @param Channel $channel
     * @return array
     */
    private function createTypes(Channel $channel)
    {
        $applicableShippingServices = $this->getSettings($channel)->getApplicableShippingServices()->toArray();

        return array_map(function (ShippingService $shippingService) use ($channel) {
            return $this->methodTypeFactory->create($channel, $shippingService);
        }, $applicableShippingServices);
    }

    /**
     * @param Channel $channel
     *
     * @return string|null
     */
    private function getIcon(Channel $channel)
    {
        return $this->integrationIconProvider->getIcon($channel);
    }
}
