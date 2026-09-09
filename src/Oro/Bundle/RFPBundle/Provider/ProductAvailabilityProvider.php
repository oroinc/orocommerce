<?php

namespace Oro\Bundle\RFPBundle\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * Provides a set of methods to check whether products can be added to RFP.
 */
class ProductAvailabilityProvider implements ProductAvailabilityProviderInterface
{
    /** @var ManagerRegistry */
    private $doctrine;

    /** @var ProductManager */
    private $productManager;

    /** @var AclHelper */
    private $aclHelper;

    public function setDoctrine(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function setProductManager(ProductManager $productManager)
    {
        $this->productManager = $productManager;
    }

    public function setAclHelper(AclHelper $aclHelper)
    {
        $this->aclHelper = $aclHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function isProductApplicableForRFP(Product $product)
    {
        return $product->getType() !== Product::TYPE_CONFIGURABLE;
    }

    public function getAllowedProductIds(array $productIds)
    {
        if (!$productIds) {
            return [];
        }

        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass(Product::class);

        $queryBuilder = $em
            ->getRepository(Product::class)
            ->getProductsQueryBuilder($productIds);

        $queryBuilder->select('p.id');

        $this->productManager->restrictQueryBuilder(
            $queryBuilder,
            ['scope' => 'rfp']
        );

        $queryBuilder
            ->andWhere('p.type != :notAllowedProductType')
            ->setParameter('notAllowedProductType', Product::TYPE_CONFIGURABLE);

        $allowedProductIds = $this->aclHelper
            ->apply($queryBuilder)
            ->getArrayResult();

        return array_column($allowedProductIds, 'id');
    }
}
