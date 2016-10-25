<?php

namespace Oro\Bundle\UPSBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\MeasureUnitInterface;
use Oro\Bundle\UPSBundle\Entity\UPSTransport as UPSTransportEntity;

class UnitsMapper
{
    const UNIT_OF_WEIGHT_KG = 'kg';
    const UNIT_OF_WEIGHT_LBS = 'lbs';

    const UNIT_OF_LENGTH_INCH = 'inch';
    const UNIT_OF_LENGTH_FOOT = 'foot';
    const UNIT_OF_LENGTH_CM = 'cm';
    const UNIT_OF_LENGTH_M = 'm';

    /**
     * @var  ManagerRegistry
     */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param string $upsUnitCode
     * @return string
     * @throws \InvalidArgumentException
     */
    public function getShippingUnitCode($upsUnitCode)
    {
        switch ($upsUnitCode) {
            case UPSTransportEntity::UNIT_OF_WEIGHT_KGS:
                return static::UNIT_OF_WEIGHT_KG;
            case UPSTransportEntity::UNIT_OF_WEIGHT_LBS:
                return static::UNIT_OF_WEIGHT_LBS;
            case UPSTransportEntity::UNIT_OF_LENGTH_INCH:
                return static::UNIT_OF_LENGTH_INCH;
            case UPSTransportEntity::UNIT_OF_LENGTH_CM:
                return static::UNIT_OF_LENGTH_CM;
            default:
                throw new \InvalidArgumentException(
                    sprintf('This unit is not UPS unit: %s.', $upsUnitCode)
                );
        }
    }

    /**
     * @param string $upsUnitCode
     * @return null|MeasureUnitInterface
     */
    public function getShippingUnitByUPSUnit($upsUnitCode)
    {
        $oroUnit = null;

        $oroUnitCode = $this->getShippingUnitCode($upsUnitCode);
        if ($oroUnitCode) {
            if ($oroUnitCode === static::UNIT_OF_WEIGHT_KG || $oroUnitCode === static::UNIT_OF_WEIGHT_LBS) {
                $repository = $this->registry->getRepository('OroShippingBundle:WeightUnit');
            } else {
                $repository = $this->registry->getRepository('OroShippingBundle:LengthUnit');
            }

            $oroUnit = $repository->findOneBy(['code' => $oroUnitCode]);
        }

        return $oroUnit;
    }

    /**
     * @param string $shippingUnitCode
     * @return string
     * @throws \InvalidArgumentException
     */
    public function getUPSUnitByCode($shippingUnitCode)
    {
        switch ($shippingUnitCode) {
            case static::UNIT_OF_WEIGHT_KG:
                return UPSTransportEntity::UNIT_OF_WEIGHT_KGS;
            case static::UNIT_OF_WEIGHT_LBS:
                return UPSTransportEntity::UNIT_OF_WEIGHT_LBS;
            case static::UNIT_OF_LENGTH_INCH:
                return UPSTransportEntity::UNIT_OF_LENGTH_INCH;
            case static::UNIT_OF_LENGTH_CM:
                return UPSTransportEntity::UNIT_OF_LENGTH_CM;
            case static::UNIT_OF_LENGTH_FOOT:
            case static::UNIT_OF_LENGTH_M:
            default:
                throw new \InvalidArgumentException(
                    sprintf('This unit is not accepted by UPS: %s.', $shippingUnitCode)
                );
        }
    }

    /**
     * @param MeasureUnitInterface $shippingUnit
     * @return string
     * @throws \InvalidArgumentException
     */
    public function getUPSUnitByShippingUnit($shippingUnit)
    {
        return $this->getUPSUnitByCode($shippingUnit->getCode());
    }
}
