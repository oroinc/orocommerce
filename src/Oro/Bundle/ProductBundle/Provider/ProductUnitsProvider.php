<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;

/**
 * Provides product units
 * gets from ProductBundle\Entity\ProductUnit repository
 * apply unitLabel formatting
 */
class ProductUnitsProvider
{
    /**
     * @var  ManagerRegistry
     */
    protected $registry;

    /**
     * @var  UnitLabelFormatterInterface
     */
    protected $formatter;

    /**
     * @param ManagerRegistry $registry
     * @param UnitLabelFormatterInterface $formatter
     */
    public function __construct(ManagerRegistry $registry, UnitLabelFormatterInterface $formatter)
    {
        $this->registry = $registry;
        $this->formatter = $formatter;
    }
    
    /**
     * @return array
     */
    public function getAvailableProductUnits()
    {
        $productUnits = $this->getRepository()->getAllUnits();

        $unitsFull = [];
        foreach ($productUnits as $unit) {
            $code = $unit->getCode();
            $unitsFull[$this->formatter->format($code)] = $code;
        }

        return $unitsFull;
    }

    /**
     * @return array
     */
    public function getAvailableProductUnitsWithPrecision()
    {
        $productUnits = $this->getRepository()->getAllUnits();

        $unitsWithPrecision = array();
        foreach ($productUnits as $unit) {
            $unitsWithPrecision[$unit->getCode()] = $unit->getDefaultPrecision();
        }

        return $unitsWithPrecision;
    }

    /**
     * @return ProductUnitRepository
     */
    protected function getRepository()
    {
        return $this->registry
            ->getManagerForClass('Oro\Bundle\ProductBundle\Entity\ProductUnit')
            ->getRepository('Oro\Bundle\ProductBundle\Entity\ProductUnit');
    }
}
