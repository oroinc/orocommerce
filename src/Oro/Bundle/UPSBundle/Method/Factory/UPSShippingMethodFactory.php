<?php

namespace Oro\Bundle\UPSBundle\Method\Factory;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ShippingBundle\Method\Factory\IntegrationShippingMethodFactoryInterface;
use Oro\Bundle\ShippingBundle\Method\Identifier\IntegrationMethodIdentifierGeneratorInterface;
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
     * @var IntegrationMethodIdentifierGeneratorInterface
     */
    private $methodIdentifierGenerator;

    /**
     * @var UPSShippingMethodTypeFactoryInterface
     */
    private $methodTypeFactory;

    /**
     * @param UPSTransport $transport
     * @param PriceRequestFactory $priceRequestFactory
     * @param LocalizationHelper $localizationHelper
     * @param ShippingPriceCache $shippingPriceCache
     * @param IntegrationMethodIdentifierGeneratorInterface $methodIdentifierGenerator
     * @param UPSShippingMethodTypeFactoryInterface $methodTypeFactory
     */
    public function __construct(
        UPSTransport $transport,
        PriceRequestFactory $priceRequestFactory,
        LocalizationHelper $localizationHelper,
        ShippingPriceCache $shippingPriceCache,
        IntegrationMethodIdentifierGeneratorInterface $methodIdentifierGenerator,
        UPSShippingMethodTypeFactoryInterface $methodTypeFactory
    ) {
        $this->transport = $transport;
        $this->priceRequestFactory = $priceRequestFactory;
        $this->localizationHelper = $localizationHelper;
        $this->shippingPriceCache = $shippingPriceCache;
        $this->methodIdentifierGenerator = $methodIdentifierGenerator;
        $this->methodTypeFactory = $methodTypeFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function create(Channel $channel)
    {
        return new UPSShippingMethod(
            $this->getIdentifier($channel),
            $this->getLabel($channel),
            $this->createTypes($channel),
            $this->getSettings($channel),
            $this->transport,
            $this->priceRequestFactory,
            $this->shippingPriceCache
        );
    }

    /**
     * @param Channel $channel
     * @return string
     */
    private function getIdentifier(Channel $channel)
    {
        return $this->methodIdentifierGenerator->generateIdentifier($channel);
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
}
