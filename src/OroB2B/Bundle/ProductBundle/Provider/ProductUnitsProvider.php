<?php

namespace OroB2B\Bundle\ProductBundle\Provider;

use Doctrine\Common\Persistence\ObjectManager;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use Symfony\Component\Translation\TranslatorInterface;

class ProductUnitsProvider
{
    private $entityManager;

    /** @var  array */
    private $productUnits;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    public function __construct(ObjectManager $entityManager, TranslatorInterface $translator)
    {
        $this->entityManager = $entityManager;
        $this->translator = $translator;
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
        foreach ($this->productUnits as $unit){
            $code = $unit->getCode();
            $unitsFull[$code] = $this->translator->trans('product_unit.'.$code.'.label.full');
        }
        return  $unitsFull;
    }
}

