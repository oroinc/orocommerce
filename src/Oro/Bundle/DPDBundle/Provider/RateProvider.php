<?php

namespace Oro\Bundle\DPDBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\DPDBundle\Entity\Rate;
use Oro\Bundle\DPDBundle\Entity\Repository\RateRepository;
use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\ShippingBundle\Model\Weight;
use Oro\Bundle\ShippingBundle\Provider\MeasureUnitConversion;
use Oro\Bundle\DPDBundle\Entity\DPDTransport as DPDTransportEntity;

class RateProvider
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var MeasureUnitConversion */
    protected $measureUnitConversion;

    /**
     * RateTablePriceProvider constructor.
     *
     * @param ManagerRegistry       $registry
     * @param MeasureUnitConversion $measureUnitConversion
     */
    public function __construct(
        ManagerRegistry $registry,
        MeasureUnitConversion $measureUnitConversion
    ) {
        $this->registry = $registry;
        $this->measureUnitConversion = $measureUnitConversion;
    }

    /**
     * @param DPDTransportEntity $transport
     * @param ShippingService    $shippingService
     * @param AddressInterface   $shippingAddress
     * @param $weight weight in kg
     *
     * @return null|string
     */
    public function getRateValue(
        DPDTransportEntity $transport,
        ShippingService $shippingService,
        AddressInterface $shippingAddress,
        $weight
    ) {
        $rateValue = null;
        if ($transport->getRatePolicy() === DPDTransportEntity::FLAT_RATE_POLICY) {
            $rateValue = $transport->getFlatRatePriceValue();
        } elseif ($transport->getRatePolicy() === DPDTransportEntity::TABLE_RATE_POLICY) {
            /** @var RateRepository $rateRepository */
            $rateRepository =
                $this->registry->getManagerForClass('OroDPDBundle:Rate')->getRepository('OroDPDBundle:Rate');
            $rate =
                $rateRepository->findFirstRateByServiceAndDestination($transport, $shippingService, $shippingAddress);

            if ($rate !== null) {
                $rateValue = $rate->getPriceValue();
            }
        }

        return $rateValue;
    }
}
