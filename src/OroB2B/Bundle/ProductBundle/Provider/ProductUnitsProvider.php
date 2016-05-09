<?php

namespace OroB2B\Bundle\ProductBundle\Provider;

use Doctrine\Common\Persistence\ObjectManager;

class ProductUnitsProvider
{
    private $entityManager;

    /** @var  array */
    private $productUnits;

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
        return $this->productUnits;
    }
}

