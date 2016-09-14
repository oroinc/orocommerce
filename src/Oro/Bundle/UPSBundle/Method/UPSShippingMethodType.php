<?php

namespace Oro\Bundle\UPSBundle\Method;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;
use Oro\Bundle\ShippingBundle\Model\Weight;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\Provider\UPSTransport as UPSTransportProvider;
use Oro\Bundle\UPSBundle\Form\Type\UPSShippingMethodOptionsType;
use Oro\Bundle\UPSBundle\Model\Package;
use Oro\Bundle\UPSBundle\Model\PriceRequest;

class UPSShippingMethodType implements ShippingMethodTypeInterface
{
    const REQUEST_OPTION = 'Rate';
    const MAX_PACKAGE_WEIGHT = 70;

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

    /** @var ManagerRegistry */
    protected $registry;

    /** @var string */
    protected $optionsConfigurationFormType;

    /**
     * @param UPSTransport $transport
     * @param UPSTransportProvider $transportProvider
     * @param ShippingService $shippingService
     * @param ManagerRegistry $registry
     */
    public function __construct(
        UPSTransport $transport,
        UPSTransportProvider $transportProvider,
        ShippingService $shippingService,
        ManagerRegistry $registry
    ) {
        $this->setIdentifier($shippingService->getCode());
        $this->setLabel($shippingService->getDescription());

        $this->transport = $transport;
        $this->transportProvider = $transportProvider;
        $this->shippingService = $shippingService;
        $this->registry = $registry;
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
     * @param string $optionsConfigurationFormType
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

        $packages = $this->createPackages($context->getLineItems(), $this->transport->getUnitOfWeight());

        if (count($packages) > 0) {
            $priceRequest = $priceRequest->setPackages($packages);
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
        }

        if ($price === null) {
            return null;
        }

        $surcharge = $typeOptions[$this->shippingService->getCode()][self::OPTION_SURCHARGE];

        return Price::create($price + (float)$surcharge, $context->getCurrency());
    }

    /**
     * @param ShippingLineItemInterface[] $lineItems
     * @param string $unitOfWeight
     * @return Package[]|array
     */
    protected function createPackages($lineItems, $unitOfWeight)
    {
        $packages = [];

        if (count($lineItems) === 0) {
            return $packages;
        }

        $productsParamsByUnit = $this->getProductsParamsByUnit($lineItems);

        if (count($productsParamsByUnit) > 0) {
            /** @var array $productsParamsByWeightUnit */
            foreach ($productsParamsByUnit as $dimensionUnit => $productsParamsByWeightUnit) {
                /** @var array $productsParams */
                foreach ($productsParamsByWeightUnit as $productsParams) {
                    $weight = 0;
                    $dimensionHeight = 0;
                    $dimensionWidth = 0;
                    $dimensionLength = 0;

                    foreach ($productsParams as $productsParam) {
                        if (($weight + $productsParam['weight']) >= self::MAX_PACKAGE_WEIGHT) {
                            $packages[] = Package::create(
                                (string)$dimensionUnit,
                                (string)$dimensionHeight,
                                (string)$dimensionWidth,
                                (string)$dimensionLength,
                                (string)$unitOfWeight,
                                (string)$weight
                            );

                            $weight = 0;
                            $dimensionHeight = 0;
                            $dimensionWidth = 0;
                            $dimensionLength = 0;
                        }

                        $weight += $productsParam['weight'];
                        $dimensionHeight += $productsParam['dimensionHeight'];
                        $dimensionWidth += $productsParam['dimensionWidth'];
                        $dimensionLength += $productsParam['dimensionLength'];
                    }

                    if ($weight > 0) {
                        $packages[] = Package::create(
                            (string)$dimensionUnit,
                            (string)$dimensionHeight,
                            (string)$dimensionWidth,
                            (string)$dimensionLength,
                            (string)$unitOfWeight,
                            (string)$weight
                        );
                    }
                }
            }
        }

        return $packages;
    }

    /**
     * @param ShippingLineItemInterface[] $lineItems
     * @return array
     */
    protected function getProductsParamsByUnit($lineItems)
    {
        $productsParamsByUnit = [];

        foreach ($lineItems as $lineItem) {
            /** @var ProductShippingOptions $productShippingOptions */
            $productShippingOptions = $this->registry
                ->getManagerForClass('OroShippingBundle:ProductShippingOptions')
                ->getRepository('OroShippingBundle:ProductShippingOptions')
                ->findOneBy(['product' => $lineItem->getProduct()]);

            $productDimensions = $productShippingOptions->getDimensions();

            $dimensionUnit = $productDimensions->getUnit()->getCode();
            $lineItemWeight = ($productShippingOptions->getWeight() instanceof Weight) ?
                $productShippingOptions->getWeight()->getValue() :
                0;
            $weightUnit = $productShippingOptions->getWeight()->getUnit()->getCode();
            if (((int)$lineItemWeight === 0) || ($dimensionUnit === '') || ($weightUnit === '')) {
                return [];
            }

            $productsParamsByUnit[strtoupper(substr($dimensionUnit, 0, 2))][strtoupper(substr($weightUnit, 0, 2))][] = [
                'dimensionUnit' => $dimensionUnit,
                'dimensionHeight' => $productDimensions->getValue()->getHeight() * $lineItem->getQuantity(),
                'dimensionWidth' => $productDimensions->getValue()->getWidth() * $lineItem->getQuantity(),
                'dimensionLength' => $productDimensions->getValue()->getLength() * $lineItem->getQuantity(),
                'weightUnit' => $weightUnit,
                'weight' => $lineItemWeight * $lineItem->getQuantity()
            ];
        }

        return $productsParamsByUnit;
    }
}
