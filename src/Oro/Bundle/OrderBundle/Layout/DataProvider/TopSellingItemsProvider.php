<?php

namespace Oro\Bundle\OrderBundle\Layout\DataProvider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Model\ProductView;
use Oro\Bundle\ProductBundle\Provider\ProductListBuilder;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * Provides top selling products.
 */
class TopSellingItemsProvider
{
    private const PRODUCT_LIST_TYPE = 'top_selling_items';
    private const DEFAULT_QUANTITY = 10;

    private ManagerRegistry $doctrine;
    private ProductManager $productManager;
    private AclHelper $aclHelper;
    private ProductListBuilder $productListBuilder;

    /** @var array|null [product view, ...] */
    private ?array $products = null;

    public function __construct(
        ManagerRegistry $doctrine,
        ProductManager $productManager,
        AclHelper $aclHelper,
        ProductListBuilder $productListBuilder
    ) {
        $this->doctrine = $doctrine;
        $this->productManager = $productManager;
        $this->aclHelper = $aclHelper;
        $this->productListBuilder = $productListBuilder;
    }

    /**
     * @return ProductView[]
     */
    public function getProducts(): array
    {
        if (null === $this->products) {
            $this->products = $this->loadProducts();
        }

        return $this->products;
    }

    private function loadProducts(): array
    {
        $qb = $this->getProductRepository()
            ->getFeaturedProductsQueryBuilder(self::DEFAULT_QUANTITY)
            ->select('product.id');
        $this->productManager->restrictQueryBuilder($qb, []);
        $rows = $this->aclHelper->apply($qb)->getArrayResult();
        if (!$rows) {
            return [];
        }

        return $this->productListBuilder->getProductsByIds(self::PRODUCT_LIST_TYPE, array_column($rows, 'id'));
    }

    private function getProductRepository(): ProductRepository
    {
        return $this->doctrine->getRepository(Product::class);
    }
}
