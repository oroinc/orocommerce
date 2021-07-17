<?php

namespace Oro\Bundle\InventoryBundle\Provider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Entity\Repository\InventoryLevelRepository;
use Oro\Bundle\InventoryBundle\Inventory\InventoryQuantityManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

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

    /**
     * {@inheritDoc}
     */
    public function canDecrement(Product $product = null)
    {
        return $this->quantityManager->shouldDecrement($product);
    }

    /**
     * {@inheritDoc}
     */
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
