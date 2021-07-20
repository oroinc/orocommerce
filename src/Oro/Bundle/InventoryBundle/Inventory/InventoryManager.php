<?php

namespace Oro\Bundle\InventoryBundle\Inventory;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Entity\Repository\InventoryLevelRepository;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;

/**
 * Provides methods to create/delete inventory levels
 */
class InventoryManager
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param ProductUnitPrecision $productUnitPrecision
     * @return null|InventoryLevel
     */
    public function createInventoryLevel(ProductUnitPrecision $productUnitPrecision)
    {
        if (!$productUnitPrecision->getProduct() instanceof Product) {
            return null;
        }

        $inventoryLevel = new InventoryLevel();
        $inventoryLevel->setProductUnitPrecision($productUnitPrecision);
        $inventoryLevel->setQuantity(0);
        $organization = $this->doctrineHelper->getEntityRepositoryForClass(Organization::class)->getFirst();
        if ($organization) {
            $inventoryLevel->setOrganization($organization);
        }

        return $inventoryLevel;
    }

    public function deleteInventoryLevel(ProductUnitPrecision $productUnitPrecision)
    {
        /** @var InventoryLevelRepository $inventoryLevelRepository */
        $inventoryLevelRepository = $this->doctrineHelper->getEntityRepositoryForClass(InventoryLevel::class);
        $inventoryLevelRepository->deleteInventoryLevelByProductAndProductUnitPrecision(
            $productUnitPrecision->getProduct(),
            $productUnitPrecision
        );
    }
}
