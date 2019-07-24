<?php

namespace Oro\Bundle\ProductBundle\Entity\Repository\Restriction;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * Provide products consider applied restrictions (ACL, Product Visibility, etc.)
 */
class RestrictedProductRepository
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var ProductManager */
    private $productManager;

    /** @var AclHelper */
    private $aclHelper;

    /** @var string */
    private $productClass;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ProductManager $productManager
     * @param AclHelper $aclHelper
     * @param string $productClass
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ProductManager $productManager,
        AclHelper $aclHelper,
        $productClass
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->productManager = $productManager;
        $this->aclHelper = $aclHelper;
        $this->productClass = $productClass;
    }

    /**
     * @param array    $productIds
     * @param int|null $limit
     * @return Product[]
     */
    public function findProducts(array $productIds = [], $limit = null)
    {
        /** @var ProductRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository($this->productClass);

        $queryBuilder = $repository->getProductsQueryBuilder($productIds);
        $queryBuilder->orderBy('p.id');

        if ($limit) {
            $queryBuilder->setMaxResults($limit);
        }

        $restrictedQueryBuilder = $this->productManager->restrictQueryBuilder($queryBuilder, []);

        return $this->aclHelper->apply($restrictedQueryBuilder)->getResult();
    }
}
