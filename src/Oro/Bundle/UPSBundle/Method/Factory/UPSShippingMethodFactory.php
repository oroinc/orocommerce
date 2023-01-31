<?php

namespace Oro\Bundle\UPSBundle\Method\Factory;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\IntegrationBundle\Provider\IntegrationIconProviderInterface;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ShippingBundle\Method\Factory\IntegrationShippingMethodFactoryInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\UPSBundle\Cache\ShippingPriceCache;
use Oro\Bundle\UPSBundle\Entity\UPSTransport as UPSSettings;
use Oro\Bundle\UPSBundle\Factory\PriceRequestFactory;
use Oro\Bundle\UPSBundle\Method\UPSShippingMethod;
use Oro\Bundle\UPSBundle\Provider\UPSTransport;

/**
 * The factory to create UPS shipping method.
 */
class UPSShippingMethodFactory implements IntegrationShippingMethodFactoryInterface
{
    private UPSTransport $transport;
    private PriceRequestFactory $priceRequestFactory;
    private LocalizationHelper $localizationHelper;
    private ShippingPriceCache $shippingPriceCache;
    private IntegrationIdentifierGeneratorInterface $integrationIdentifierGenerator;
    private UPSShippingMethodTypeFactoryInterface $methodTypeFactory;
    private IntegrationIconProviderInterface $integrationIconProvider;

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
    public function create(Channel $channel): ShippingMethodInterface
    {
        /** @var UPSSettings $transport */
        $transport = $channel->getTransport();
        $types = [];
        $applicableShippingServices = $transport->getApplicableShippingServices()->toArray();
        foreach ($applicableShippingServices as $shippingService) {
            $types[] = $this->methodTypeFactory->create($channel, $shippingService);
        }

        return new UPSShippingMethod(
            $this->integrationIdentifierGenerator->generateIdentifier($channel),
            (string)$this->localizationHelper->getLocalizedValue($transport->getLabels()),
            $this->integrationIconProvider->getIcon($channel),
            $types,
            $transport,
            $this->transport,
            $this->priceRequestFactory,
            $this->shippingPriceCache,
            $channel->isEnabled()
        );
    }
}
