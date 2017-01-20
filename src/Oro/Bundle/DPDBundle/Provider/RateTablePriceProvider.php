<?php

namespace Oro\Bundle\DPDBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\DPDBundle\Entity\Rate;
use Oro\Bundle\DPDBundle\Entity\Repository\RateRepository;
use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\ShippingBundle\Provider\MeasureUnitConversion;
use Oro\Bundle\DPDBundle\Entity\DPDTransport as DPDTransportEntity;

class RateTablePriceProvider
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var MeasureUnitConversion */
    protected $measureUnitConversion;

    /**
     * RateTablePriceProvider constructor.
     * @param ManagerRegistry $registry
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
     * @param ShippingService $shippingService
     * @param AddressInterface $shippingAddress
     * @return Rate|null
     */
    public function getRateByServiceAndDestination(
        DPDTransportEntity $transport,
        ShippingService $shippingService,
        AddressInterface $shippingAddress
    ) {
        /** @var RateRepository $rateRepository */
        $rateRepository = $this->registry->getManagerForClass('OroDPDBundle:Rate')->getRepository('OroDPDBundle:Rate');
        $rates = $rateRepository->findRatesByServiceAndDestination($transport, $shippingService, $shippingAddress);
        if (!empty($rates)) {
            return reset($rates);
        }

        return null;
    }
}
