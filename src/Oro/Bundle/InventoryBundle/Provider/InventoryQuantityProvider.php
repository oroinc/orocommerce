<?php

namespace Oro\Bundle\InventoryBundle\Provider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Entity\Repository\InventoryLevelRepository;
use Oro\Bundle\InventoryBundle\Inventory\InventoryQuantityManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

/**
 * Provides inventory quantity information for products.
 *
 * This implementation retrieves inventory levels from the database and uses the
 * {@see InventoryQuantityManager} to calculate available quantities and determine whether
 * inventory can be decremented based on product configuration and current inventory levels.
 */
class InventoryQuantityProvider implements InventoryQuantityProviderInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var InventoryQuantityManager */
    protected $quantityManager;

    public function __construct(DoctrineHelper $doctrineHelper, InventoryQuantityManager $quantityManager)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->quantityManager = $quantityManager;
    }

    #[\Override]
    public function canDecrement(?Product $product = null)
    {
        return $this->quantityManager->shouldDecrement($product);
    }

    #[\Override]
    public function getAvailableQuantity(Product $product, ProductUnit $unit)
    {
        $inventoryLevel = $this->getInventoryLevel($product, $unit);
        if (!$inventoryLevel) {
            return 0;
        }

        return $this->quantityManager->getAvailableQuantity($inventoryLevel);
    }

    /**
     * @param Product $product
     * @param ProductUnit $productUnit
     * @return InventoryLevel
     */
    protected function getInventoryLevel(Product $product, ProductUnit $productUnit)
    {
        /** @var InventoryLevelRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository(InventoryLevel::class);
        return $repository->getLevelByProductAndProductUnit(
            $product,
            $productUnit
        );
    }
}
