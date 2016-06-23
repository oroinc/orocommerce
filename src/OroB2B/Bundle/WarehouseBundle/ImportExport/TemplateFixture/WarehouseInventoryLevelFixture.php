<?php

namespace OroB2B\Bundle\WarehouseBundle\ImportExport\TemplateFixture;

use Oro\Bundle\ImportExportBundle\TemplateFixture\AbstractTemplateRepository;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateFixtureInterface;
use Oro\Component\Testing\Unit\Entity\Stub\StubEnumValue;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\WarehouseBundle\Entity\Warehouse;
use OroB2B\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel;

class WarehouseInventoryLevelFixture extends AbstractTemplateRepository implements TemplateFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getEntityClass()
    {
        return 'OroB2B\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel';
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        return $this->getEntityData('Example WarehouseInventoryLevel');
    }

    /**
     * {@inheritdoc}
     */
    protected function createEntity($key)
    {
        return new WarehouseInventoryLevel();
    }

    /**
     * @param string  $key
     * @param WarehouseInventoryLevel $entity
     */
    public function fillEntityData($key, $entity)
    {
        $inventoryStatus = new StubEnumValue(Product::INVENTORY_STATUS_IN_STOCK, 'in stock');

        $product = new Product();
        $product->setSku('product.1')
            ->setStatus('enabled')
            ->setInventoryStatus($inventoryStatus)
            ->setHasVariants(true);

        $additionalProductUnit = (new ProductUnit())
            ->setCode('liter')
            ->setDefaultPrecision(0);

        $additionalProductUnitPrecision = $this
            ->createEntityWithId('OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision', 2);
        $additionalProductUnitPrecision
            ->setUnit($additionalProductUnit)
            ->setPrecision($additionalProductUnit->getDefaultPrecision())
            ->setConversionRate(5)
            ->setSell(false);
        $additionalProductUnitPrecision->setProduct($product);

        $warehouse = new Warehouse();
        $warehouse->setName('First Warehouse');

        $entity->setProductUnitPrecision($additionalProductUnitPrecision)
            ->setQuantity(12)
            ->setWarehouse($warehouse);
    }

    /**
     * @param string $className
     * @param int $id
     *
     * @return object
     */
    protected function createEntityWithId($className, $id)
    {
        $entity = new $className;

        $reflectionClass = new \ReflectionClass($className);
        $method = $reflectionClass->getProperty('id');
        $method->setAccessible(true);
        $method->setValue($entity, $id);

        return $entity;
    }
}
