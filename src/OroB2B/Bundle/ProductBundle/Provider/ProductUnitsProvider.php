<?php

namespace OroB2B\Bundle\ProductBundle\Provider;

class ProductUnitsProvider
{
    private $entityManager;

    /** @var  array */
    private $productUnits;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->productUnits = $this->entityManager
            ->getRepository('OroB2B\ProductBundle\ProductUnitRepository')
            ->getAllUnits();
    }
    /**
     * @return array
     */
    public function getAvailableProductUnits()
    {
        return $this->productUnits();
    }
}

