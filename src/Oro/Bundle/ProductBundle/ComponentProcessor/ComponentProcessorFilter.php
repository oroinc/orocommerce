<?php

namespace Oro\Bundle\ProductBundle\ComponentProcessor;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;

class ComponentProcessorFilter implements ComponentProcessorFilterInterface
{
    /** @var  ProductManager */
    protected $productManager;

    /** @var  ManagerRegistry */
    protected $registry;

    /** @var string */
    protected $productClass;

    /**
     * @param ProductManager $productManager
     * @param ManagerRegistry $registry
     */
    public function __construct(ProductManager $productManager, ManagerRegistry $registry)
    {
        $this->productManager = $productManager;
        $this->registry = $registry;
    }

    /**
     * @param string $productClass
     */
    public function setProductClass($productClass)
    {
        $this->productClass = $productClass;
    }

    /**
     * {@inheritdoc}
     */
    public function filterData(array $data, array $dataParameters)
    {
        $products = [];
        foreach ($data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY] as $product) {
            $products[strtoupper($product[ProductDataStorage::PRODUCT_SKU_KEY])] = $product;
        }
        $data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY] = [];

        if (empty($products)) {
            return $data;
        }

        $queryBuilder = $this->getRepository()->getFilterSkuQueryBuilder(array_keys($products));
        $queryBuilder = $this->productManager->restrictQueryBuilder($queryBuilder, $dataParameters);

        $filteredProducts = $queryBuilder->getQuery()->getResult();
        foreach ($filteredProducts as $product) {
            $data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY][] = $products[strtoupper($product['sku'])];
        }

        return $data;
    }

    /**
     * @return ProductRepository
     */
    protected function getRepository()
    {
        return $this->registry->getManagerForClass($this->productClass)->getRepository($this->productClass);
    }
}
