<?php

namespace OroB2B\Bundle\ProductBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;

class ProductUnitsProvider
{
    /** @var  ManagerRegistry */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
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
            $unitsFull[$code] = 'orob2b.product_unit.'.$code.'.label.full';
        }
        return  $unitsFull;
    }

    /**
     * @return ProductUnitRepository
     */
    protected function getRepository()
    {
        $manager = $this->registry
            ->getManagerForClass('OroB2B\Bundle\ProductBundle\Entity\ProductUnit');
        return $this->registry
            ->getManagerForClass('OroB2B\Bundle\ProductBundle\Entity\ProductUnit')
            ->getRepository('OroB2B\Bundle\ProductBundle\Entity\ProductUnit');
    }
}
