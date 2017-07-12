<?php

namespace Oro\Bundle\OrderBundle\Layout\DataProvider;

use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Provider\ProductsProviderInterface;

class TopSellingItemsProvider implements ProductsProviderInterface
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
    public function __construct(
        ProductRepository $productRepository,
        ProductManager $productManager
    ) {
        $this->productRepository = $productRepository;
        $this->productManager = $productManager;
    }

    /**
     * {@inheritDoc}
     */
    public function getProducts()
    {
        $queryBuilder = $this->productRepository->getFeaturedProductsQueryBuilder(self::DEFAULT_QUANTITY);
        $this->productManager->restrictQueryBuilder($queryBuilder, []);
        return $queryBuilder->getQuery()->getResult();
    }
}
