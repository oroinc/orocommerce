<?php

namespace Oro\Bundle\UPSBundle\Factory;

use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemsAwareInterface;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingKitItemLineItem;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptionsInterface;
use Oro\Bundle\ShippingBundle\Provider\MeasureUnitConversion;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\Model\Package;
use Oro\Bundle\UPSBundle\Model\PriceRequest;
use Oro\Bundle\UPSBundle\Provider\UnitsMapper;

/**
 * Creates {@see PriceRequest} by {@see UPSTransport}, {@see ShippingContextInterface},
 * Request option and {@see ShippingService}.
 */
class PriceRequestFactory
{
    public const MAX_PACKAGE_WEIGHT_KGS = 70;
    public const MAX_PACKAGE_WEIGHT_LBS = 150;

    protected MeasureUnitConversion $measureUnitConversion;
    protected UnitsMapper $unitsMapper;
    protected SymmetricCrypterInterface $symmetricCrypter;

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
     * @param ShippingService|null $shippingService
     *
     * @return PriceRequest|null
     * @throws \UnexpectedValueException
     */
    public function create(
        UPSTransport $transport,
        ShippingContextInterface $context,
        $requestOption,
        ?ShippingService $shippingService = null
    ) {
        if (!$context->getShippingAddress()) {
            return null;
        }

        $decryptedPassword = $this->symmetricCrypter->decryptData($transport->getUpsApiPassword());

        $priceRequest = (new PriceRequest())
            ->setUsername($transport->getUpsApiUser())
            ->setPassword($decryptedPassword)
            ->setAccessLicenseNumber($transport->getUpsApiKey())
            ->setClientId($transport->getUpsClientId())
            ->setClientSecret($transport->getUpsClientSecret())
            ->setRequestOption($requestOption)
            ->setShipperName($transport->getUpsShippingAccountName())
            ->setShipperNumber($transport->getUpsShippingAccountNumber())
            ->setShipperAddress($context->getShippingOrigin())
            ->setShipToAddress($context->getShippingAddress())
            ->setShipFromName($transport->getUpsShippingAccountName())
            ->setShipFromAddress($context->getShippingOrigin());

        if (null !== $shippingService) {
            $priceRequest
                ->setServiceCode($shippingService->getCode())
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
     * @param ShippingLineItem[] $lineItems
     * @param string $unitOfWeight
     * @param int $weightLimit
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
     * @param ShippingLineItem[] $lineItems
     * @param string $upsWeightUnit
     *
     * @return array
     */
    protected function getAllItemsWeightArray(array $lineItems, $upsWeightUnit): array
    {
        $productsParamsByUnit = [];
        $lineItems = array_merge($lineItems, $this->getKitItemLineItems($lineItems));

        foreach ($lineItems as $lineItem) {
            $upsWeight = $this->getLineItemWeightByUnit($lineItem, $upsWeightUnit);

            // Allow UPS delivery for cases when empty or not valid shipping options for kit product
            if (!$upsWeight && $lineItem->getProduct()?->isKit()) {
                continue;
            }

            if (!$upsWeight) {
                return [];
            }

            $productsParamsByUnit[] = array_fill(0, $lineItem->getQuantity(), $upsWeight);
        }

        return array_merge(...$productsParamsByUnit);
    }

    /**
     * @param ProductShippingOptionsInterface $lineItem
     * @param string $weightUnit
     *
     * @return float|null
     */
    protected function getLineItemWeightByUnit(ProductShippingOptionsInterface $lineItem, $weightUnit)
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

    /**
     * @param ShippingLineItem[] $lineItems
     *
     * @return ShippingKitItemLineItem[]
     */
    private function getKitItemLineItems(array $lineItems): array
    {
        $kitLineItems = [];
        foreach ($lineItems as $lineItem) {
            if ($lineItem instanceof ProductKitItemLineItemsAwareInterface && $lineItem->getProduct()?->isKit()) {
                $kitLineItems = array_merge($kitLineItems, $lineItem->getKitItemLineItems()->toArray());
            }
        }

        return $kitLineItems;
    }
}
