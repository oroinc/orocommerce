<?php

namespace OroB2B\Bundle\ProductBundle\Layout\DataProvider;

use OroB2B\Bundle\ProductBundle\Entity\Manager\ProductManager;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\Repository\ProductRepository;

class FeaturedProductsProvider
{
    /**
     * @var array
     */
    protected $data;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var ProductManager $productManager
     */
    protected $productManager;

    /**
     * @param ProductRepository $productRepository
     * @param ProductManager $productManager
     */
    public function __construct(ProductRepository $productRepository, ProductManager $productManager)
    {
        $this->productRepository = $productRepository;
        $this->productManager = $productManager;
    }

    public function getAll()
    {
        $queryBuilder = $this->productRepository->getProductWithNamesQueryBuilder()
            ->setMaxResults(10)
            ->orderBy('product.id', 'ASC');
        $this->productRepository->selectImages($queryBuilder);
        $products = $this->productManager
            ->restrictQueryBuilder($queryBuilder, [])->getQuery()->getResult();
        return $products;
    }
}
