<?php

namespace Oro\Bundle\InventoryBundle\ImportExport\TemplateFixture;

use Oro\Bundle\ImportExportBundle\TemplateFixture\AbstractTemplateRepository;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateFixtureInterface;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Component\Testing\Unit\Entity\Stub\StubEnumValue;

/**
 *  Provides Inventory Level sample export template data
 */
class InventoryLevelFixture extends AbstractTemplateRepository implements TemplateFixtureInterface
{
    /** @var LocalizationManager */
    private $localizationManager;

    /**
     * @param LocalizationManager $localizationManager
     */

    public function setLocalizationManager(LocalizationManager $localizationManager)
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
        $inventoryStatus = new StubEnumValue(Product::INVENTORY_STATUS_IN_STOCK, 'In Stock');

        $localization = $this->localizationManager->getDefaultLocalization();

        $name = new LocalizedFallbackValue();
        $name->setString('Product Name');

        $localizedName = new LocalizedFallbackValue();
        $localizedName->setLocalization($localization)
            ->setString('US Product Name')
            ->setFallback('system');

        $product->setSku('product-1')
            ->setInventoryStatus($inventoryStatus)
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
}
