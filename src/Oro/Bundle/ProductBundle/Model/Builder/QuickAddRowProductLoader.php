<?php

namespace Oro\Bundle\ProductBundle\Model\Builder;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * Loads products for {@see QuickAddRowProductMapper}.
 */
class QuickAddRowProductLoader
{
    private ManagerRegistry $doctrine;
    private ProductManager $productManager;
    private AclHelper $aclHelper;

    public function __construct(ManagerRegistry $doctrine, ProductManager $productManager, AclHelper $aclHelper)
    {
        $this->doctrine = $doctrine;
        $this->productManager = $productManager;
        $this->aclHelper = $aclHelper;
    }

    /**
     * @param string[] $skusUppercase
     *
     * @return Product[]
     */
    public function loadProducts(array $skusUppercase): array
    {
        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass(Product::class);
        $qb = $em->createQueryBuilder()
            ->select('product, primaryUnitPrecision')
            ->from(Product::class, 'product')
            ->leftJoin('product.primaryUnitPrecision', 'primaryUnitPrecision')
            ->where('product.skuUppercase IN (:product_skus)')
            ->setParameter('product_skus', $skusUppercase)
            ->orderBy('product.organization, product.id');
        $qb = $this->productManager->restrictQueryBuilder($qb, []);

        // Configurable products require additional option selection that is not implemented yet.
        // Thus we need to hide configurable products.
        $qb
            ->andWhere('product.type <> :configurable_type')
            ->setParameter('configurable_type', Product::TYPE_CONFIGURABLE);

        return $this->aclHelper->apply($qb)->getResult();
    }
}
