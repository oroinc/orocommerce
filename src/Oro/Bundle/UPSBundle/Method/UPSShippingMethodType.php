<?php

namespace Oro\Bundle\UPSBundle\Method;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\Factory\PriceRequestFactory;
use Oro\Bundle\UPSBundle\Provider\UPSTransport as UPSTransportProvider;
use Oro\Bundle\UPSBundle\Form\Type\UPSShippingMethodOptionsType;

class UPSShippingMethodType implements ShippingMethodTypeInterface
{
    const REQUEST_OPTION = 'Rate';

    const OPTION_SURCHARGE = 'surcharge';

    /** @var string|int */
    protected $identifier;

    /** @var string */
    protected $label;

    /** @var UPSTransport */
    protected $transport;

    /** @var UPSTransportProvider */
    protected $transportProvider;

    /** @var ShippingService */
    protected $shippingService;

    /** @var PriceRequestFactory */
    protected $priceRequestFactory;

    /**
     * @param UPSTransport $transport
     * @param UPSTransportProvider $transportProvider
     * @param ShippingService $shippingService
     * @param PriceRequestFactory $priceRequestFactory
     */
    public function __construct(
        UPSTransport $transport,
        UPSTransportProvider $transportProvider,
        ShippingService $shippingService,
        PriceRequestFactory $priceRequestFactory
    ) {
        $this->setIdentifier($shippingService->getCode());
        $this->setLabel($shippingService->getDescription());

        $this->transport = $transport;
        $this->transportProvider = $transportProvider;
        $this->shippingService = $shippingService;
        $this->priceRequestFactory = $priceRequestFactory;
    }

    /**
     * @param int|string $identifier
     * @return $this
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;

        return $this;
    }

    /**
     * @param string $label
     * @return $this
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * {@inheritdoc}
     */
    public function getSortOrder()
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionsConfigurationFormType()
    {
        return UPSShippingMethodOptionsType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function calculatePrice(ShippingContextInterface $context, array $methodOptions, array $typeOptions)
    {
        $price = null;

        $priceRequest = $this->priceRequestFactory->create(
            $this->transport,
            $context,
            self::REQUEST_OPTION,
            $this->shippingService
        );
        
        if (count($priceRequest->getPackages()) > 0) {
            $prices = $this->transportProvider->getPrices($priceRequest, $this->transport);
            if ($prices === null) {
                return null;
            }
            $packagePrice = $prices->getPriceByService($this->shippingService->getCode());
            if ($packagePrice !== null) {
                $price += (float)$packagePrice->getValue();
            } else {
                return null;
            }

            $methodSurcharge = array_key_exists(UPSShippingMethod::OPTION_SURCHARGE, $methodOptions) ?
                $methodOptions[self::OPTION_SURCHARGE] :
                0
            ;

            $typeSurcharge = array_key_exists(self::OPTION_SURCHARGE, $typeOptions) ?
                $typeOptions[self::OPTION_SURCHARGE] :
                0
            ;

            return Price::create(
                $price + (float)$methodSurcharge + (float)$typeSurcharge,
                $packagePrice->getCurrency()
            );
        }
        return null;
    }
}
