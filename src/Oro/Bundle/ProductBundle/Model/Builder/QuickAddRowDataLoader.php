<?php

namespace Oro\Bundle\ProductBundle\Model\Builder;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\Mapping\ProductMapperDataLoaderInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * Loads products for the service that maps a product for each row in QuickAddRowCollection.
 */
class QuickAddRowDataLoader implements ProductMapperDataLoaderInterface
{
    private ManagerRegistry $doctrine;
    private ProductManager $productManager;
    private AclHelper $aclHelper;
    private array $notAllowedProductTypes = [];

    public function __construct(ManagerRegistry $doctrine, ProductManager $productManager, AclHelper $aclHelper)
    {
        $this->doctrine = $doctrine;
        $this->productManager = $productManager;
        $this->aclHelper = $aclHelper;
    }

    public function setNotAllowedProductTypes(array $notAllowedProductTypes): void
    {
        $this->notAllowedProductTypes = $notAllowedProductTypes;
    }

    /**
     *
     * @param string[] $skusUppercase
     *
     * @return Product[]
     */
    #[\Override]
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

        // Configurable/kit products require additional option selection is not implemented yet.
        // Thus we need to hide configurable/kit products.
        if (!empty($this->notAllowedProductTypes)) {
            $qb->andWhere($qb->expr()->notIn('product.type', ':notAllowedProductTypes'))
                ->setParameter('notAllowedProductTypes', $this->notAllowedProductTypes);
        }

        return $this->aclHelper->apply($qb)->getResult();
    }
}
