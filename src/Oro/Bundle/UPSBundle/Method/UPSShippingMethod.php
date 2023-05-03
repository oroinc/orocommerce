<?php

namespace Oro\Bundle\UPSBundle\Method;

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

/**
 * Represents UPS shipping method.
 */
class UPSShippingMethod implements
    ShippingMethodInterface,
    ShippingMethodIconAwareInterface,
    PricesAwareShippingMethodInterface,
    ShippingTrackingAwareInterface
{
    public const OPTION_SURCHARGE = 'surcharge';

    private const REQUEST_OPTION = 'Shop';

    private const TRACKING_URL =
        'https://www.ups.com/WebTracking/processInputRequest?TypeOfInquiryNumber=T&InquiryNumber1=';
    private const TRACKING_REGEX =
        '/\b(1Z ?[0-9A-Z]{3} ?[0-9A-Z]{3} ?[0-9A-Z]{2} ?[0-9A-Z]{4} ?[0-9A-Z]{3}'
        . ' ?[0-9A-Z]|[\dT]\d\d\d ?\d\d\d\d ?\d\d\d)\b/ix';

    private string $identifier;
    private string $label;
    private ?string $icon;
    private array $types;
    private UPSSettings $transport;
    private UPSTransportProvider $transportProvider;
    private PriceRequestFactory $priceRequestFactory;
    private ShippingPriceCache $cache;
    private bool $enabled;

    public function __construct(
        string $identifier,
        string $label,
        ?string $icon,
        array $types,
        UPSSettings $transport,
        UPSTransportProvider $transportProvider,
        PriceRequestFactory $priceRequestFactory,
        ShippingPriceCache $cache,
        bool $enabled
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
    public function isGrouped(): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * {@inheritDoc}
     */
    public function getIcon(): ?string
    {
        return $this->icon;
    }

    /**
     * {@inheritDoc}
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    /**
     * {@inheritDoc}
     */
    public function getType(string $identifier): ?ShippingMethodTypeInterface
    {
        $methodTypes = $this->getTypes();
        if ($methodTypes !== null) {
            foreach ($methodTypes as $methodType) {
                if ($methodType->getIdentifier() === $identifier) {
                    return $methodType;
                }
            }
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getOptionsConfigurationFormType(): ?string
    {
        return UPSShippingMethodOptionsType::class;
    }

    /**
     * {@inheritDoc}
     */
    public function getSortOrder(): int
    {
        return 20;
    }

    /**
     * {@inheritDoc}
     */
    public function calculatePrices(
        ShippingContextInterface $context,
        array $methodOptions,
        array $optionsByTypes
    ): array {
        $optionsDefaults = [self::OPTION_SURCHARGE => 0];
        $methodOptions = array_merge($optionsDefaults, $methodOptions);

        if (\count($this->getTypes()) < 1) {
            return [];
        }

        $prices = $this->fetchPrices($context, array_keys($optionsByTypes));

        foreach ($prices as $typeId => $price) {
            $typeOptions = array_merge($optionsDefaults, $optionsByTypes[$typeId]);
            $prices[$typeId] = $price
                ->setValue(array_sum([
                    (float)$price->getValue(),
                    (float)$methodOptions[self::OPTION_SURCHARGE],
                    (float)$typeOptions[self::OPTION_SURCHARGE]
                ]));
        }

        return $prices;
    }

    /**
     * {@inheritDoc}
     */
    public function getTrackingLink(string $number): ?string
    {
        if (!preg_match(self::TRACKING_REGEX, $number, $match)) {
            return null;
        }

        return self::TRACKING_URL . $match[0];
    }

    private function fetchPrices(ShippingContextInterface $context, array $types): array
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
        $notCachedTypesNumber = \count($notCachedTypes);

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
