<?php

namespace OroB2B\Bundle\ProductBundle\ComponentProcessor;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\ProductBundle\Entity\Manager\ProductManager;
use OroB2B\Bundle\ProductBundle\Entity\Repository\ProductRepository;

class ComponentProcessorFilter
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
     * @param array $data
     * @param array $dataParameters
     * @return array
     */
    public function filterData(array $data, array $dataParameters)
    {
        $products = [];
        foreach ($data['entity_items_data'] as $product) {
            $products[strtoupper($product['productSku'])] = $product;
        }
        $data['entity_items_data'] = [];

        if (empty($products)) {
            return $data;
        }

        $queryBuilder = $this->getRepository()->getFilterSkuQueryBuilder(array_keys($products));
        $queryBuilder = $this->productManager->restrictQueryBuilderByProductVisibility($queryBuilder, $dataParameters);

        $filteredProducts = $queryBuilder->getQuery()->getResult();
        foreach ($filteredProducts as $product) {
            $data['entity_items_data'][] = $products[strtoupper($product['sku'])];
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
