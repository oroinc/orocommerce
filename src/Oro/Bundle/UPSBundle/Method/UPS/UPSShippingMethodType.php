<?php

namespace Oro\Bundle\UPSBundle\Method\UPS;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\Provider\UPSTransport as UPSTransportProvider;
use Oro\Bundle\UPSBundle\Form\Type\UPSShippingMethodOptionsType;
use Oro\Bundle\UPSBundle\Model\Package;
use Oro\Bundle\UPSBundle\Model\PriceRequest;

class UPSShippingMethodType implements ShippingMethodTypeInterface
{
    const REQUEST_OPTION = 'Rate';
    const PACKAGING_TYPE_CODE = '00';
    const MAX_PACKAG_WEIGHT = 70;

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

    /**
     * @param UPSTransport $transport
     * @param UPSTransportProvider $transportProvider
     * @param ShippingService $shippingService
     */
    public function __construct(
        UPSTransport $transport,
        UPSTransportProvider $transportProvider,
        ShippingService $shippingService
    ) {
        $this->setIdentifier($shippingService->getCode());
        $this->setLabel($shippingService->getDescription());

        $this->transport = $transport;
        $this->transportProvider = $transportProvider;
        $this->shippingService = $shippingService;
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
     * @param mixed $optionsConfigurationFormType
     * @return $this
     */
    public function setOptionsConfigurationFormType($optionsConfigurationFormType)
    {
        $this->optionsConfigurationFormType = $optionsConfigurationFormType;

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

        $priceRequest = (new PriceRequest())
            ->setUsername($this->transport->getApiUser())
            ->setPassword($this->transport->getApiPassword())
            ->setAccessLicenseNumber($this->transport->getApiKey())
            ->setRequestOption(self::REQUEST_OPTION)
            ->setShipperName($this->transport->getShippingAccountName())
            ->setShipperNumber($this->transport->getShippingAccountNumber())
            ->setShipperAddress($context->getShippingOrigin())
            ->setShipToAddress($context->getShippingAddress())
            ->setShipFromName($this->transport->getShippingAccountName())
            ->setShipFromAddress($context->getShippingOrigin())
            ->setServiceCode($this->shippingService->getCode())
            ->setServiceDescription($this->shippingService->getDescription())
        ;

        $weight = 0;
        $lineItems = $context->getLineItems();
        if (count($lineItems) > 0) {
            foreach ($lineItems as $lineItem) {
                $lineItemWeight = $lineItem->getWeight()->getValue();
                if (empty($lineItemWeight)) {
                    return null;
                }

                if (($weight + $lineItemWeight) >= self::MAX_PACKAG_WEIGHT) {
                    $package = (new Package())
                        ->setPackagingTypeCode(self::PACKAGING_TYPE_CODE)
                        ->setWeightCode($this->transport->getUnitOfWeight())
                        ->setWeight($weight)
                    ;
                    $priceRequest = $priceRequest->setPackages([$package]);
                    $packagePrice = $this->transportProvider->getPrices($priceRequest, $this->transport);
                    if ($packagePrice !== null) {
                        $price += $packagePrice;
                    }

                    $weight = 0;
                }

                $weight += $lineItemWeight;
            }

            if ($weight > 0) {
                $package = (new Package())
                    ->setPackagingTypeCode(self::PACKAGING_TYPE_CODE)
                    ->setWeightCode($this->transport->getUnitOfWeight())
                    ->setWeight($weight);
                $priceRequest = $priceRequest->setPackages([$package]);
                $packagePrice = $this->transportProvider->getPrices($priceRequest, $this->transport);
                if ($packagePrice !== null) {
                    $price += $packagePrice;
                }
            }
        }

        $surcharge = $typeOptions[self::OPTION_SURCHARGE];

        return Price::create($price + (float)$surcharge, $context->getCurrency());
    }
}
