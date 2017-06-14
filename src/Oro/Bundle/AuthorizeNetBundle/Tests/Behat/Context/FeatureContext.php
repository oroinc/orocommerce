<?php

namespace Oro\Bundle\AuthorizeNetBundle\Tests\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Inventory\InventoryManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Form;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroPageObjectAware;
use Oro\Bundle\TestFrameworkBundle\Tests\Behat\Context\PageObjectDictionary;
use Oro\Bundle\WarehouseBundle\Entity\Warehouse;
use Oro\Bundle\WarehouseBundle\SystemConfig\WarehouseConfig;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FeatureContext extends OroFeatureContext implements OroPageObjectAware, KernelAwareContext
{
    use PageObjectDictionary, KernelDictionary;

    const PRODUCT_SKU = 'SKU123';
    const PRODUCT_INVENTORY_QUANTITY = 100;

    /**
     * @Given There are products in the system available for order
     */
    public function setProductInventoryLevelQuantity()
    {
        /** @var DoctrineHelper $doctrineHelper */
        $doctrineHelper = $this->getContainer()->get('oro_entity.doctrine_helper');

        /** @var Product $product */
        $product = $doctrineHelper->getEntityRepositoryForClass(Product::class)
            ->findOneBy(['sku' => self::PRODUCT_SKU]);

        $inventoryLevelEntityManager = $doctrineHelper->getEntityManagerForClass(InventoryLevel::class);
        $inventoryLevelRepository = $inventoryLevelEntityManager->getRepository(InventoryLevel::class);

        /** @var InventoryLevel $inventoryLevel */
        $inventoryLevel = $inventoryLevelRepository->findOneBy(['product' => $product]);
        if (!$inventoryLevel) {
            /** @var InventoryManager $inventoryManager */
            $inventoryManager = $this->getContainer()->get('oro_inventory.manager.inventory_manager');
            $inventoryLevel = $inventoryManager->createInventoryLevel($product->getPrimaryUnitPrecision());
        }
        $inventoryLevel->setQuantity(self::PRODUCT_INVENTORY_QUANTITY);

        // package commerce-ee available
        if (method_exists($inventoryLevel, 'setWarehouse')) {
            $warehouseEntityManager = $doctrineHelper->getEntityManagerForClass(Warehouse::class);
            $warehouseRepository = $warehouseEntityManager->getRepository(Warehouse::class);

            $warehouse = $warehouseRepository->findOneBy([]);

            if (!$warehouse) {
                $warehouse = new Warehouse();
                $warehouse
                    ->setName('Test Warehouse 222')
                    ->setOwner($product->getOwner())
                    ->setOrganization($product->getOrganization());
                $warehouseEntityManager->persist($warehouse);
                $warehouseEntityManager->flush();
            }

            $inventoryLevel->setWarehouse($warehouse);
            $inventoryLevelEntityManager->persist($inventoryLevel);
            $inventoryLevelEntityManager->flush();

            $warehouseConfig = new WarehouseConfig($warehouse, 1);
            $configManager = $this->getContainer()->get('oro_config.global');
            $configManager->set('oro_warehouse.enabled_warehouses', [$warehouseConfig]);
            $configManager->flush();
        } else {
            $inventoryLevelEntityManager->persist($inventoryLevel);
            $inventoryLevelEntityManager->flush();
        }
    }
}
