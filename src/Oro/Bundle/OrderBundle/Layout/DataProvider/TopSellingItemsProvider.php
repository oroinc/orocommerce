<?php

namespace Oro\Bundle\OrderBundle\Layout\DataProvider;

use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;

class TopSellingItemsProvider
{
    const DEFAULT_QUANTITY = 10;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var ProductManager
     */
    protected $productManager;

    /**
     * @param ProductRepository $productRepository
     * @param ProductManager    $productManager
     */
    public function __construct(ProductRepository $productRepository, ProductManager $productManager)
    {
        $this->productRepository = $productRepository;
        $this->productManager    = $productManager;
    }

    /**
     * @param int $quantity
     *
     * @return Product[]
     */
    public function getAll($quantity = self::DEFAULT_QUANTITY)
    {
        $queryBuilder = $this->productRepository->getFeaturedProductsQueryBuilder($quantity);
        $this->productManager->restrictQueryBuilder($queryBuilder, []);

        return $queryBuilder->getQuery()->getResult();
    }
}
