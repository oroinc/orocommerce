<?php

namespace Oro\Bundle\ProductBundle\Layout\DataProvider;

use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;

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
        $this->productManager->restrictQueryBuilder($queryBuilder, []);
        return $queryBuilder->getQuery()->getResult();
    }
}
