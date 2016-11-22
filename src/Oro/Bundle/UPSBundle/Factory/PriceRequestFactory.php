<?php

namespace Oro\Bundle\UPSBundle\Factory;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use Oro\Bundle\ShippingBundle\Model\Weight;
use Oro\Bundle\ShippingBundle\Provider\MeasureUnitConversion;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\Model\Package;
use Oro\Bundle\UPSBundle\Model\PriceRequest;
use Oro\Bundle\UPSBundle\Provider\UnitsMapper;

class PriceRequestFactory
{
    const MAX_PACKAGE_WEIGHT_KGS = 70;
    const MAX_PACKAGE_WEIGHT_LBS = 150;

    /** @var ManagerRegistry */
    protected $registry;

    /** @var MeasureUnitConversion */
    protected $measureUnitConversion;

    /** @var UnitsMapper */
    protected $unitsMapper;

    /**
     * @param ManagerRegistry $registry
     * @param MeasureUnitConversion $measureUnitConversion,
     * @param UnitsMapper $unitsMapper
     */
    public function __construct(
        ManagerRegistry $registry,
        MeasureUnitConversion $measureUnitConversion,
        UnitsMapper $unitsMapper
    ) {
        $this->registry = $registry;
        $this->measureUnitConversion = $measureUnitConversion;
        $this->unitsMapper = $unitsMapper;
    }

    /**
     * @param UPSTransport $transport
     * @param ShippingContextInterface $context
     * @param string $requestOption
     * @param ShippingService|null $shippingService
     * @return PriceRequest
     */
    public function create(
        UPSTransport $transport,
        ShippingContextInterface $context,
        $requestOption,
        ShippingService $shippingService = null
    ) {
        $priceRequest = (new PriceRequest())
            ->setUsername($transport->getApiUser())
            ->setPassword($transport->getApiPassword())
            ->setAccessLicenseNumber($transport->getApiKey())
            ->setRequestOption($requestOption)
            ->setShipperName($transport->getShippingAccountName())
            ->setShipperNumber($transport->getShippingAccountNumber())
            ->setShipperAddress($context->getShippingOrigin())
            ->setShipToAddress($context->getShippingAddress())
            ->setShipFromName($transport->getShippingAccountName())
            ->setShipFromAddress($context->getShippingOrigin());
        
        if (null !== $shippingService) {
            $priceRequest->setServiceCode($shippingService->getCode())
                ->setServiceDescription($shippingService->getDescription());
        }

        $unitOfWeight = $transport->getUnitOfWeight();
        if ($unitOfWeight === UPSTransport::UNIT_OF_WEIGHT_KGS) {
            $weightLimit = self::MAX_PACKAGE_WEIGHT_KGS;
        } else {
            $weightLimit = self::MAX_PACKAGE_WEIGHT_LBS;
        }

        $packages = $this->createPackages($context->getLineItems(), $unitOfWeight, $weightLimit);
        if (count($packages) > 0) {
            $priceRequest->setPackages($packages);
        }

        return $priceRequest;
    }

    /**
     * @param ShippingLineItemInterface[] $lineItems
     * @param string $unitOfWeight
     * @param int $weightLimit
     * @return Package[]|array
     */
    protected function createPackages($lineItems, $unitOfWeight, $weightLimit)
    {
        $packages = [];

        if (count($lineItems) === 0) {
            return $packages;
        }

        $productsParamsByUnit = $this->getProductsParamsByUnit($lineItems, $unitOfWeight);
        if (count($productsParamsByUnit) > 0) {
            /** @var array $productsParamsByWeightUnit */
            foreach ($productsParamsByUnit as $dimensionUnit => $productsParamsByWeightUnit) {
                $weight = 0;
                $dimensionHeight = 0;
                $dimensionWidth = 0;
                $dimensionLength = 0;

                /** @var array $productsParams */
                foreach ($productsParamsByWeightUnit as $productsParams) {
                    if ($productsParams['weight'] > $weightLimit) {
                        return [];
                    }
                    if (($weight + $productsParams['weight']) > $weightLimit) {
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

                    $weight += $productsParams['weight'];
                    $dimensionHeight += $productsParams['dimensionHeight'];
                    $dimensionWidth += $productsParams['dimensionWidth'];
                    $dimensionLength += $productsParams['dimensionLength'];
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

        return $packages;
    }

    /**
     * @param ShippingLineItemInterface[] $lineItems
     * @param string $upsWeightUnit
     * @return array
     */
    protected function getProductsParamsByUnit($lineItems, $upsWeightUnit)
    {
        $productsParamsByUnit = [];
        $shippingWeightUnitCode = $this->unitsMapper->getShippingUnitCode($upsWeightUnit);

        foreach ($lineItems as $lineItem) {
            /** @var ProductShippingOptions $productShippingOptions */
            $productShippingOptions = $this->registry
                ->getManagerForClass('OroShippingBundle:ProductShippingOptions')
                ->getRepository('OroShippingBundle:ProductShippingOptions')
                ->findOneBy(['product' => $lineItem->getProduct()]);

            $productDimensions = $productShippingOptions->getDimensions();

            $dimensionUnit = $productDimensions->getUnit() ? $productDimensions->getUnit()->getCode() : null;
            $lineItemWeight = null;
            if ($productShippingOptions->getWeight() instanceof Weight) {
                /** @var Weight|null $lineItemWeight */
                $lineItemWeight = $this->measureUnitConversion->convert(
                    $productShippingOptions->getWeight(),
                    $shippingWeightUnitCode
                );

                $lineItemWeight = $lineItemWeight !== null ? $lineItemWeight->getValue() : null;
            }
            if (!$lineItemWeight || !$dimensionUnit) {
                return [];
            }

            for ($i = 0; $i < $lineItem->getQuantity(); $i++) {
                $productsParamsByUnit[strtoupper(substr($dimensionUnit, 0, 2))][] = [
                    'dimensionUnit' => $dimensionUnit,
                    'dimensionHeight' => $productDimensions->getValue()->getHeight(),
                    'dimensionWidth' => $productDimensions->getValue()->getWidth(),
                    'dimensionLength' => $productDimensions->getValue()->getLength(),
                    'weightUnit' => $upsWeightUnit,
                    'weight' => $lineItemWeight
                ];
            }
        }

        return $productsParamsByUnit;
    }
}
