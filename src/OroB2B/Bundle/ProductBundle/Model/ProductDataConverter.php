<?php

namespace OroB2B\Bundle\ProductBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManager;

use OroB2B\Bundle\ProductBundle\Entity\Product;

class ProductDataConverter
{
    const PRODUCT_KEY = 'id';
    const QUANTITY_KEY = 'qty';

    /** @var ManagerRegistry */
    protected $registry;

    /** @var string */
    protected $dataClass;

    /** @var EntityManager */
    protected $manager;

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
     * @param array $data
     * @return array|QuickAddProductInformation[]
     */
    public function getProductsInfoByStoredData(array $data)
    {
        $manager = $this->getEntityManager();
        $result = [];
        foreach ($data as $dataRow) {
            /** @var Product $product */
            $product = $manager->getReference($this->dataClass, $dataRow[self::PRODUCT_KEY]);
            $result[] = (new QuickAddProductInformation())
                ->setProduct($product)
                ->setQuantity((float)$dataRow[self::QUANTITY_KEY]);
        }

        return $result;
    }

    /**
     * @return ObjectRepository
     */
    protected function getRepository()
    {
        return $this->getEntityManager()->getRepository($this->dataClass);
    }

    /**
     * @return EntityManager|null
     */
    protected function getEntityManager()
    {
        if (!$this->manager) {
            $this->manager = $this->registry->getManagerForClass($this->dataClass);
        }

        return $this->manager;
    }
}
