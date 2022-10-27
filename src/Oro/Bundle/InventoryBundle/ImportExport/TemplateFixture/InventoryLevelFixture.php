<?php

namespace Oro\Bundle\InventoryBundle\ImportExport\TemplateFixture;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ImportExportBundle\TemplateFixture\AbstractTemplateRepository;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateFixtureInterface;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;

/**
 * Provides Inventory Level sample export template data.
 */
class InventoryLevelFixture extends AbstractTemplateRepository implements TemplateFixtureInterface
{
    private LocalizationManager $localizationManager;

    public function __construct(LocalizationManager $localizationManager)
    {
        $this->localizationManager = $localizationManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityClass()
    {
        return InventoryLevel::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        return $this->getEntityData('Example Inventory Level');
    }

    /**
     * {@inheritdoc}
     */
    protected function createEntity($key)
    {
        return new InventoryLevel();
    }

    /**
     * @param string  $key
     * @param InventoryLevel $entity
     */
    public function fillEntityData($key, $entity)
    {
        $product = new Product();

        $localization = $this->localizationManager->getDefaultLocalization();

        $name = new ProductName();
        $name->setString('Product Name');

        $localizedName = new ProductName();
        $localizedName->setLocalization($localization)
            ->setString('US Product Name')
            ->setFallback('system');

        $product->setSku('product-1')
            ->setInventoryStatus($this->createInventoryStatus(Product::INVENTORY_STATUS_IN_STOCK, 'In Stock'))
            ->addName($name)
            ->addName($localizedName);

        $entity->setQuantity(50);

        $unitPrecision = new ProductUnitPrecision();
        $unit = new ProductUnit();
        $unit->setCode('liter');
        $unit->setDefaultPrecision(1);
        $unitPrecision->setUnit($unit);
        $unitPrecision->setProduct($product);
        $entity->setProductUnitPrecision($unitPrecision);
    }

    private function createInventoryStatus(string $id, string $name): AbstractEnumValue
    {
        $enumValueClassName = ExtendHelper::buildEnumValueClassName('prod_inventory_status');

        return new $enumValueClassName($id, $name);
    }
}
