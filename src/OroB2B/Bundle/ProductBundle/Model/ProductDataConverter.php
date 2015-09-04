<?php

namespace OroB2B\Bundle\ProductBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectRepository;

use OroB2B\Bundle\ProductBundle\Entity\Product;

class ProductDataConverter
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var string */
    protected $dataClass;

    /** @var array */
    protected $products = [];

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    /**
     * @return ObjectRepository
     */
    protected function getRepository()
    {
        return $this->registry->getManagerForClass($this->dataClass)->getRepository($this->dataClass);
    }

    /**
     * @param string $sku
     * @return null|Product
     */
    public function convertSkuToProduct($sku)
    {
        if (!array_key_exists($sku, $this->products)) {
            $product = null;

            if ($sku) {
                $product = $this->getRepository()->findOneBy(['sku' => $sku]);
            }

            $this->products[$sku] = $product;
        }

        return $this->products[$sku];
    }
}
