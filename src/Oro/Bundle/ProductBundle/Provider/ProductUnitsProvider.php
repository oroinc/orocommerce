<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use Oro\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;

class ProductUnitsProvider
{
    /**
     * @var  ManagerRegistry
     */
    protected $registry;

    /**
     * @var  ProductUnitLabelFormatter
     */
    protected $formatter;

    /**
     * @param ManagerRegistry $registry
     * @param ProductUnitLabelFormatter $formatter
     */
    public function __construct(ManagerRegistry $registry, ProductUnitLabelFormatter $formatter)
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
            $unitsFull[$code] = $this->formatter->format($code);
        }
        return  $unitsFull;
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
