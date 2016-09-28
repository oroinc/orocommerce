<?php

namespace Oro\Bundle\UPSBundle\Factory;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use Oro\Bundle\ShippingBundle\Model\Weight;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\Model\Package;
use Oro\Bundle\UPSBundle\Model\PriceRequest;

class PriceRequestFactory
{
    const MAX_PACKAGE_WEIGHT_KGS = 70;
    const MAX_PACKAGE_WEIGHT_LBS = 150;

    /** @var ManagerRegistry */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(
        ManagerRegistry $registry
    ) {
        $this->registry = $registry;
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
                        if (($weight + $productsParam['weight']) >= $weightLimit) {
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

            $dimensionUnit = $productDimensions->getUnit() ? $productDimensions->getUnit()->getCode() : null;
            $lineItemWeight = null;
            $weightUnit = null;
            if ($productShippingOptions->getWeight() instanceof Weight) {
                $lineItemWeight = $productShippingOptions->getWeight()->getValue();
                $weightUnit = $productShippingOptions->getWeight()->getUnit()->getCode();
            }
            if (!$lineItemWeight || !$dimensionUnit || !$weightUnit) {
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
