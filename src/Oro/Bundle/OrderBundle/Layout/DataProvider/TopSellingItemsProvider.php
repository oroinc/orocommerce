<?php

namespace Oro\Bundle\OrderBundle\Layout\DataProvider;

use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Provider\ProductsProviderInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * Top selling products
 */
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
     * @var AclHelper
     */
    private $aclHelper;

    public function __construct(
        ProductRepository $productRepository,
        ProductManager $productManager,
        AclHelper $aclHelper
    ) {
        $this->productRepository = $productRepository;
        $this->productManager = $productManager;
        $this->aclHelper = $aclHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function getProducts()
    {
        $queryBuilder = $this->productRepository->getFeaturedProductsQueryBuilder(self::DEFAULT_QUANTITY);
        $this->productManager->restrictQueryBuilder($queryBuilder, []);

        return $this->aclHelper->apply($queryBuilder)->getResult();
    }
}
