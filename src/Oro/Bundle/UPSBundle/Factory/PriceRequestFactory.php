<?php

namespace Oro\Bundle\UPSBundle\Factory;

use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface;
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

    /** @var MeasureUnitConversion */
    protected $measureUnitConversion;

    /** @var UnitsMapper */
    protected $unitsMapper;

    /** @var SymmetricCrypterInterface */
    protected $symmetricCrypter;

    /**
     * PriceRequestFactory constructor.
     */
    public function __construct(
        MeasureUnitConversion $measureUnitConversion,
        UnitsMapper $unitsMapper,
        SymmetricCrypterInterface $symmetricCrypter
    ) {
        $this->measureUnitConversion = $measureUnitConversion;
        $this->unitsMapper = $unitsMapper;
        $this->symmetricCrypter = $symmetricCrypter;
    }

    /**
     * @param UPSTransport             $transport
     * @param ShippingContextInterface $context
     * @param string                   $requestOption
     * @param ShippingService|null     $shippingService
     *
     * @return PriceRequest|null
     * @throws \UnexpectedValueException
     */
    public function create(
        UPSTransport $transport,
        ShippingContextInterface $context,
        $requestOption,
        ShippingService $shippingService = null
    ) {
        if (!$context->getShippingAddress()) {
            return null;
        }

        $decryptedPassword = $this->symmetricCrypter->decryptData($transport->getUpsApiPassword());

        $priceRequest = (new PriceRequest())
            ->setUsername($transport->getUpsApiUser())
            ->setPassword($decryptedPassword)
            ->setAccessLicenseNumber($transport->getUpsApiKey())
            ->setRequestOption($requestOption)
            ->setShipperName($transport->getUpsShippingAccountName())
            ->setShipperNumber($transport->getUpsShippingAccountNumber())
            ->setShipperAddress($context->getShippingOrigin())
            ->setShipToAddress($context->getShippingAddress())
            ->setShipFromName($transport->getUpsShippingAccountName())
            ->setShipFromAddress($context->getShippingOrigin());

        if (null !== $shippingService) {
            $priceRequest->setServiceCode($shippingService->getCode())
                ->setServiceDescription($shippingService->getDescription());
        }

        $unitOfWeight = $transport->getUpsUnitOfWeight();
        if ($unitOfWeight === UPSTransport::UNIT_OF_WEIGHT_KGS) {
            $weightLimit = self::MAX_PACKAGE_WEIGHT_KGS;
        } else {
            $weightLimit = self::MAX_PACKAGE_WEIGHT_LBS;
        }

        $packages = $this->createPackages($context->getLineItems()->toArray(), $unitOfWeight, $weightLimit);
        if (count($packages) > 0) {
            $priceRequest->setPackages($packages);

            return $priceRequest;
        }

        return null;
    }

    /**
     * @param ShippingLineItemInterface[] $lineItems
     * @param string                      $unitOfWeight
     * @param int                         $weightLimit
     *
     * @return Package[]|array
     * @throws \UnexpectedValueException
     */
    protected function createPackages($lineItems, $unitOfWeight, $weightLimit)
    {
        $packages = [];

        if (count($lineItems) === 0) {
            return $packages;
        }

        $weightArray = $this->getAllItemsWeightArray($lineItems, $unitOfWeight);

        $packageWeight = 0;

        foreach ($weightArray as $itemWeight) {
            if ($itemWeight > $weightLimit) {
                return [];
            }
            if (($packageWeight + $itemWeight) > $weightLimit) {
                $packages[] = Package::create($unitOfWeight, $packageWeight);

                $packageWeight = 0;
            }

            $packageWeight += $itemWeight;
        }

        if ($packageWeight > 0) {
            $packages[] = Package::create($unitOfWeight, $packageWeight);
        }

        return $packages;
    }

    /**
     * @param ShippingLineItemInterface[] $lineItems
     * @param string                      $upsWeightUnit
     *
     * @return array
     */
    protected function getAllItemsWeightArray(array $lineItems, $upsWeightUnit): array
    {
        $productsParamsByUnit = [];

        foreach ($lineItems as $lineItem) {
            $upsWeight = $this->getLineItemWeightByUnit($lineItem, $upsWeightUnit);

            if (!$upsWeight) {
                return [];
            }

            $productsParamsByUnit[] = array_fill(0, $lineItem->getQuantity(), $upsWeight);
        }

        return array_merge(...$productsParamsByUnit);
    }

    /**
     * @param ShippingLineItemInterface $lineItem
     * @param string                    $weightUnit
     *
     * @return float|null
     */
    protected function getLineItemWeightByUnit(ShippingLineItemInterface $lineItem, $weightUnit)
    {
        $upsWeight = null;
        $lineItemWeight = $lineItem->getWeight();

        if ($lineItemWeight !== null && $lineItemWeight->getValue()) {
            $shippingWeightUnitCode = $this->unitsMapper->getShippingUnitCode($weightUnit);

            $upsWeight = $this->measureUnitConversion->convert($lineItemWeight, $shippingWeightUnitCode);

            if ($upsWeight !== null) {
                $upsWeight = $upsWeight->getValue();
            }
        }

        return $upsWeight;
    }
}
