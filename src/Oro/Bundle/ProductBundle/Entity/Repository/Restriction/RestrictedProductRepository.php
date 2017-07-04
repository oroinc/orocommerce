<?php

namespace Oro\Bundle\ProductBundle\Entity\Repository\Restriction;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;

class RestrictedProductRepository
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var ProductManager */
    private $productManager;

    /** @var string */
    private $productClass;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ProductManager $productManager
     * @param string         $productClass
     */
    public function __construct(DoctrineHelper $doctrineHelper, ProductManager $productManager, $productClass)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->productManager = $productManager;
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

        if ($limit) {
            $queryBuilder->setMaxResults($limit);
        }

        return $this->productManager->restrictQueryBuilder($queryBuilder, [])->getQuery()->getResult();
    }
}
