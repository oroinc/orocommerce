<?php

namespace Oro\Bundle\UPSBundle\Method;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;
use Oro\Bundle\UPSBundle\Cache\ShippingPriceCache;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Oro\Bundle\UPSBundle\Entity\UPSTransport as UPSSettings;
use Oro\Bundle\UPSBundle\Factory\PriceRequestFactory;
use Oro\Bundle\UPSBundle\Form\Type\UPSShippingMethodOptionsType;
use Oro\Bundle\UPSBundle\Provider\UPSTransport as UPSTransportProvider;

/**
 * Represents UPS shipping method type.
 */
class UPSShippingMethodType implements ShippingMethodTypeInterface
{
    private const REQUEST_OPTION = 'Rate';

    private string $identifier;
    private string $label;
    private string $methodId;
    private ShippingService $shippingService;
    private UPSSettings $transport;
    private UPSTransportProvider $transportProvider;
    private PriceRequestFactory $priceRequestFactory;
    private ShippingPriceCache $cache;

    public function __construct(
        string $identifier,
        string $label,
        string $methodId,
        ShippingService $shippingService,
        UPSSettings $transport,
        UPSTransportProvider $transportProvider,
        PriceRequestFactory $priceRequestFactory,
        ShippingPriceCache $cache
    ) {
        $this->identifier = $identifier;
        $this->label = $label;
        $this->methodId = $methodId;
        $this->shippingService = $shippingService;
        $this->transport = $transport;
        $this->transportProvider = $transportProvider;
        $this->priceRequestFactory = $priceRequestFactory;
        $this->cache = $cache;
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
    public function getSortOrder(): int
    {
        return 0;
    }

    /**
     * {@inheritDoc}
     */
    public function getOptionsConfigurationFormType(): ?string
    {
        return UPSShippingMethodOptionsType::class;
    }

    /**
     * @return ShippingService
     */
    public function getShippingService()
    {
        return $this->shippingService;
    }

    /**
     * {@inheritDoc}
     */
    public function calculatePrice(
        ShippingContextInterface $context,
        array $methodOptions,
        array $typeOptions
    ): ?Price {
        $priceRequest = $this->priceRequestFactory->create(
            $this->transport,
            $context,
            self::REQUEST_OPTION,
            $this->shippingService
        );

        if (\count($priceRequest->getPackages()) < 1) {
            return null;
        }

        $cacheKey = $this->cache->createKey($this->transport, $priceRequest, $this->methodId, $this->getIdentifier());
        if (!$this->cache->containsPrice($cacheKey)) {
            $priceResponse = $this->transportProvider->getPriceResponse($priceRequest, $this->transport);
            if (!$priceResponse) {
                return null;
            }
            $price = $priceResponse->getPriceByService($this->shippingService->getCode());
            if (!$price) {
                return null;
            }
            $this->cache->savePrice($cacheKey, $price);
        } else {
            $price = $this->cache->fetchPrice($cacheKey);
        }

        $optionsDefaults = [
            UPSShippingMethod::OPTION_SURCHARGE => 0,
        ];
        $methodOptions = array_merge($optionsDefaults, $methodOptions);
        $typeOptions = array_merge($optionsDefaults, $typeOptions);

        return $price->setValue(array_sum([
            (float)$price->getValue(),
            (float)$methodOptions[UPSShippingMethod::OPTION_SURCHARGE],
            (float)$typeOptions[UPSShippingMethod::OPTION_SURCHARGE]
        ]));
    }
}
