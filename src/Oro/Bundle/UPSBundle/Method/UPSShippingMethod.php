<?php

namespace Oro\Bundle\UPSBundle\Method;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\PricesAwareShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodIconAwareInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingTrackingAwareInterface;
use Oro\Bundle\UPSBundle\Cache\ShippingPriceCache;
use Oro\Bundle\UPSBundle\Entity\UPSTransport as UPSSettings;
use Oro\Bundle\UPSBundle\Factory\PriceRequestFactory;
use Oro\Bundle\UPSBundle\Form\Type\UPSShippingMethodOptionsType;
use Oro\Bundle\UPSBundle\Provider\UPSTransport as UPSTransportProvider;

class UPSShippingMethod implements
    ShippingMethodInterface,
    ShippingMethodIconAwareInterface,
    PricesAwareShippingMethodInterface,
    ShippingTrackingAwareInterface
{
    const IDENTIFIER = 'ups';
    const OPTION_SURCHARGE = 'surcharge';
    const REQUEST_OPTION = 'Shop';

    const TRACKING_URL = 'https://www.ups.com/WebTracking/processInputRequest?TypeOfInquiryNumber=T&InquiryNumber1=';
    const TRACKING_REGEX = '/\b
                            (1Z ?[0-9A-Z]{3} ?[0-9A-Z]{3} 
                            ?[0-9A-Z]{2} ?[0-9A-Z]{4} ?[0-9A-Z]{3} ?[0-9A-Z]|
                            [\dT]\d\d\d ?\d\d\d\d ?\d\d\d)
                            \b/ix';

    /**
     * @var UPSTransportProvider
     */
    protected $transportProvider;

    /**
     * @var Channel
     */
    protected $channel;

    /**
     * @var PriceRequestFactory
     */
    protected $priceRequestFactory;

    /**
     * @var ShippingPriceCache
     */
    protected $cache;

    /**
     * @var string
     */
    private $identifier;

    /**
     * @var string
     */
    private $label;

    /**
     * @var string|null
     */
    private $icon;

    /**
     * @var array
     */
    private $types;

    /**
     * @var UPSSettings
     */
    private $transport;

    /**
     * @var bool
     */
    private $enabled;

    /**
     * @param string               $identifier
     * @param string               $label
     * @param string|null          $icon
     * @param array                $types
     * @param UPSSettings          $transport
     * @param UPSTransportProvider $transportProvider
     * @param PriceRequestFactory  $priceRequestFactory
     * @param ShippingPriceCache   $cache
     * @param bool                 $enabled
     */
    public function __construct(
        $identifier,
        $label,
        $icon,
        array $types,
        UPSSettings $transport,
        UPSTransportProvider $transportProvider,
        PriceRequestFactory $priceRequestFactory,
        ShippingPriceCache $cache,
        $enabled
    ) {
        $this->identifier = $identifier;
        $this->label = $label;
        $this->icon = $icon;
        $this->types = $types;
        $this->transport = $transport;
        $this->transportProvider = $transportProvider;
        $this->priceRequestFactory = $priceRequestFactory;
        $this->cache = $cache;
        $this->enabled = $enabled;
    }

    /**
     * {@inheritDoc}
     */
    public function isGrouped()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * {@inheritDoc}
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * @return ShippingMethodTypeInterface[]|array
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * @param string $identifier
     * @return UPSShippingMethodType|null
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
     * {@inheritDoc}
     */
    public function calculatePrices(ShippingContextInterface $context, array $methodOptions, array $optionsByTypes)
    {
        $optionsDefaults = [static::OPTION_SURCHARGE => 0];
        $methodOptions = array_merge($optionsDefaults, $methodOptions);

        if (count($this->getTypes()) < 1) {
            return [];
        }

        $prices = $this->fetchPrices($context, array_keys($optionsByTypes));

        foreach ($prices as $typeId => $price) {
            $typeOptions = array_merge($optionsDefaults, $optionsByTypes[$typeId]);
            $prices[$typeId] = $price
                ->setValue(array_sum([
                    (float)$price->getValue(),
                    (float)$methodOptions[static::OPTION_SURCHARGE],
                    (float)$typeOptions[static::OPTION_SURCHARGE]
                ]));
        }

        return $prices;
    }

    /**
     * @param string $number
     * @return string|null
     */
    public function getTrackingLink($number)
    {
        if (!preg_match(self::TRACKING_REGEX, $number, $match)) {
            return null;
        }

        return self::TRACKING_URL . $match[0];
    }

    /**
     * @param ShippingContextInterface $context
     * @param array                    $types
     * @return array
     */
    private function fetchPrices(ShippingContextInterface $context, array $types)
    {
        $prices = [];

        $transport = $this->transport;
        $priceRequest = $this->priceRequestFactory->create($transport, $context, self::REQUEST_OPTION);
        if (!$priceRequest) {
            return $prices;
        }

        $cacheKey = $this->cache->createKey($transport, $priceRequest, $this->getIdentifier(), null);

        foreach ($types as $typeId) {
            $cacheKey->setTypeId($typeId);
            if ($this->cache->containsPrice($cacheKey)) {
                $prices[$typeId] = $this->cache->fetchPrice($cacheKey);
            }
        }

        $notCachedTypes = array_diff($types, array_keys($prices));
        $notCachedTypesNumber = count($notCachedTypes);

        if ($notCachedTypesNumber > 0) {
            if ($notCachedTypesNumber === 1) {
                $typeId = reset($notCachedTypes);
                $shippingService = $this->getType($typeId)->getShippingService();
                $priceRequest->setServiceCode($shippingService->getCode())
                    ->setServiceDescription($shippingService->getDescription());
            }
            $priceResponse = $this->transportProvider->getPriceResponse($priceRequest, $transport);
            if ($priceResponse) {
                foreach ($notCachedTypes as $typeId) {
                    $price = $priceResponse->getPriceByService($typeId);
                    if ($price) {
                        $cacheKey->setTypeId($typeId);
                        $this->cache->savePrice($cacheKey, $price);
                        $prices[$typeId] = $price;
                    }
                }
            }
        }

        return $prices;
    }
}
