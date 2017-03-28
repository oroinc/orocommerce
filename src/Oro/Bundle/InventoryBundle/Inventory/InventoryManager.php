<?php

namespace Oro\Bundle\InventoryBundle\Inventory;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Entity\Repository\InventoryLevelRepository;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;

class InventoryManager
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
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

        return $inventoryLevel;
    }

    /**
     * @param ProductUnitPrecision $productUnitPrecision
     */
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
