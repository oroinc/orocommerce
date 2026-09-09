<?php

namespace Oro\Bundle\RFPBundle\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * Provides a set of methods to check whether products can be added to RFP.
 */
class ProductRFPAvailabilityProvider
{
    private ConfigManager $configManager;
    private ManagerRegistry $doctrine;
    private AclHelper $aclHelper;
    private ?array $allowedInventoryStatuses = null;
    private array $notAllowedProductTypes = [];
    private ProductManager $productManager;

    public function __construct(
        ConfigManager $configManager,
        ManagerRegistry $doctrine,
        AclHelper $aclHelper,
        ProductManager $productManager
    ) {
        $this->configManager = $configManager;
        $this->doctrine = $doctrine;
        $this->aclHelper = $aclHelper;
        $this->productManager = $productManager;
    }

    public function setNotAllowedProductTypes(array $notAllowedProductTypes): void
    {
        $this->notAllowedProductTypes = $notAllowedProductTypes;
    }

    public function isProductAllowedForRFP(Product $product): bool
    {
        if (!$product->isEnabled()) {
            return false;
        }

        if (\in_array($product->getType(), $this->notAllowedProductTypes, true)) {
            return false;
        }

        $inventoryStatus = $product->getInventoryStatus()?->getId();
        if (!$inventoryStatus) {
            return false;
        }

        return \in_array($inventoryStatus, $this->getAllowedInventoryStatuses(), true);
    }

    public function hasProductsAllowedForRFP(array $productsIds): bool
    {
        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass(Product::class);
        $qb = $em->createQueryBuilder()
            ->from(Product::class, 'p')
            ->select('p')
            ->where('p.id = :id');
        foreach ($productsIds as $productId) {
            $qb->setParameter('id', $productId);
            $product = $this->aclHelper->apply($qb)->getOneOrNullResult();
            if (null !== $product && $this->isProductAllowedForRFP($product)) {
                return true;
            }
        }

        return false;
    }

    public function getAllowedProductIds(array $productIds): array
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

        if ($this->notAllowedProductTypes) {
            $queryBuilder
                ->andWhere('p.type NOT IN (:notAllowedProductTypes)')
                ->setParameter('notAllowedProductTypes', $this->notAllowedProductTypes);
        }

        $allowedProductIds = $this->aclHelper->apply($queryBuilder)->getArrayResult();

        return array_column($allowedProductIds, 'id');
    }

    private function getAllowedInventoryStatuses(): array
    {
        if (null === $this->allowedInventoryStatuses) {
            $this->allowedInventoryStatuses = (array)$this->configManager->get('oro_rfp.frontend_product_visibility');
        }

        return $this->allowedInventoryStatuses;
    }
}
