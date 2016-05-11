<?php

namespace OroB2B\Bundle\ProductBundle\Provider;

use Doctrine\Common\Persistence\ObjectManager;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

class ProductUnitsProvider
{
    private $entityManager;

    /** @var  array */
    private $productUnits;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager $entityManager */
    public function __construct(ObjectManager $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->productUnits = $this->entityManager
            ->getRepository('OroB2BProductBundle:ProductUnit')
            ->getAllUnits();
    }
    /**
     * @return array
     */
    public function getAvailableProductUnits()
    {
        $unitsFull = [];
        foreach ($this->productUnits as $unit) {
            $code = $unit->getCode();
            $unitsFull[$code] = 'orob2b.product_unit.'.$code.'.label.full';
        }
        return  $unitsFull;
    }
}

