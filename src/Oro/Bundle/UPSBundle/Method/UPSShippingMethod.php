<?php

namespace Oro\Bundle\UPSBundle\Method;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\PricesAwareShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\Factory\PriceRequestFactory;
use Oro\Bundle\UPSBundle\Form\Type\UPSShippingMethodOptionsType;
use Oro\Bundle\UPSBundle\Provider\UPSTransport as UPSTransportProvider;

class UPSShippingMethod implements ShippingMethodInterface, PricesAwareShippingMethodInterface
{
    const IDENTIFIER = 'ups';
    const OPTION_SURCHARGE = 'surcharge';
    const REQUEST_OPTION = 'Shop';

    /** @var UPSTransportProvider */
    protected $transportProvider;

    /** @var Channel */
    protected $channel;

    /** @var PriceRequestFactory */
    protected $priceRequestFactory;

    /** @var LocalizationHelper */
    protected $localizationHelper;

    /**
     * @param UPSTransportProvider $transportProvider
     * @param Channel $channel
     * @param PriceRequestFactory $priceRequestFactory
     * @param LocalizationHelper $localizationHelper
     */
    public function __construct(
        UPSTransportProvider $transportProvider,
        Channel $channel,
        PriceRequestFactory $priceRequestFactory,
        LocalizationHelper $localizationHelper
    ) {
        $this->transportProvider = $transportProvider;
        $this->channel = $channel;
        $this->priceRequestFactory = $priceRequestFactory;
        $this->localizationHelper = $localizationHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function isGrouped()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return static::IDENTIFIER . '_' . $this->channel->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        /** @var UPSTransport $transport */
        $transport = $this->channel->getTransport();
        return $this->localizationHelper->getLocalizedValue($transport->getLabels());
    }

    /**
     * @return ShippingMethodTypeInterface[]|array
     */
    public function getTypes()
    {
        $types = [];

        /** @var UPSTransport $transport */
        $transport = $this->channel->getTransport();
        /** @var ShippingService[] $shippingServices */
        $shippingServices = $transport->getApplicableShippingServices();
        if (count($shippingServices) > 0) {
            foreach ($shippingServices as $shippingService) {
                $types[] = new UPSShippingMethodType(
                    $transport,
                    $this->transportProvider,
                    $shippingService,
                    $this->priceRequestFactory
                );
            }
        }

        return $types;
    }

    /**
     * @param string $identifier
     * @return ShippingMethodTypeInterface|null
     */
    public function getType($identifier)
    {
        $methodTypes = $this->getTypes();
        if ($methodTypes !== null) {
            foreach ($methodTypes as $methodType) {
                if ($methodType->getIdentifier() === (string)$identifier) {
                    return $methodType;
                }
            }
        }

        return null;
    }

    /**
     * @return string
     */
    public function getOptionsConfigurationFormType()
    {
        return UPSShippingMethodOptionsType::class;
    }

    /**
     * @return int
     */
    public function getSortOrder()
    {
        return 20;
    }

    /**
     * {@inheritdoc}
     */
    public function calculatePrices(ShippingContextInterface $context, array $methodOptions, array $optionsByTypes)
    {
        $prices = [];

        $methodSurcharge = array_key_exists(UPSShippingMethod::OPTION_SURCHARGE, $methodOptions) ?
            $methodOptions[self::OPTION_SURCHARGE] :
            0
        ;

        /** @var UPSTransport $transport */
        $transport = $this->channel->getTransport();

        $types = $this->getTypes();
        if (count($types) > 0) {
            $priceRequest = $this->priceRequestFactory->create($transport, $context, self::REQUEST_OPTION);
 
            if (count($priceRequest->getPackages()) > 0) {
                $upsPrices = $this->transportProvider->getPrices($priceRequest, $transport);
                if ($upsPrices) {
                    foreach ($upsPrices->getPricesByServices() as $service => $price) {
                        if (array_key_exists($service, $optionsByTypes)) {
                            $typeSurcharge = $optionsByTypes[$service][self::OPTION_SURCHARGE] ?: 0;

                            $prices[$service] = Price::create(
                                (float)$price->getValue() + (float)$methodSurcharge + (float)$typeSurcharge,
                                $price->getCurrency()
                            );
                        }
                    }
                }
            }
        }

        return $prices;
    }
}
