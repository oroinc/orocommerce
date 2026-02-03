<?php

namespace Oro\Bundle\ProductBundle\ImportExport\TemplateFixture;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
use Oro\Bundle\ImportExportBundle\TemplateFixture\AbstractTemplateRepository;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateFixtureInterface;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductDescription;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemProduct;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\ProductBundle\Entity\ProductShortDescription;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;

/**
 * Provides products sample export template data.
 */
class ProductFixture extends AbstractTemplateRepository implements TemplateFixtureInterface
{
    public const PRODUCT_SIMPLE  = 'Product Simple';
    public const PRODUCT_SIMPLE2 = 'Product Second Simple';
    public const PRODUCT_KIT     = 'Product Kit';

    private static array $productData = [
        self::PRODUCT_SIMPLE => [
            'sku' => 'sku_001',
            'type' => Product::TYPE_SIMPLE,
            'name' => 'Product Simple',
            'description' => 'Product Simple Description',
            'shortDescription' => 'Product Simple Short Description',
            'status' => Product::STATUS_ENABLED,
            'inventory_status' => Product::INVENTORY_STATUS_IN_STOCK,
            'inventoryStatusName' => 'In Stock',
            'primaryUnitCode' => 'item',
            'additionalUnitCode' => 'kg',
        ],
        self::PRODUCT_SIMPLE2 => [
            'sku' => 'sku_002',
            'type' => Product::TYPE_SIMPLE,
            'name' => 'Product Second Simple',
            'description' => 'Product Second Simple Description',
            'shortDescription' => 'Product Second Simple Short Description',
            'status' => Product::STATUS_ENABLED,
            'inventory_status' => Product::INVENTORY_STATUS_IN_STOCK,
            'inventoryStatusName' => 'In Stock',
            'primaryUnitCode' => 'item',
            'additionalUnitCode' => 'set',
        ],
        self::PRODUCT_KIT    => [
            'sku' => 'sku_003',
            'type' => Product::TYPE_KIT,
            'name' => 'Product Kit',
            'description' => 'Product Kit Description',
            'shortDescription' => 'Product Kit Short Description',
            'status' => Product::STATUS_DISABLED,
            'inventory_status' => Product::INVENTORY_STATUS_OUT_OF_STOCK,
            'inventoryStatusName' => 'Out of Stock',
            'primaryUnitCode' => 'item',
            'additionalUnitCode' => 'set',
        ]
    ];

    private static array $kitItemData = [
        [
            'unitCode' => 'item',
            'label' => 'Base Unit',
            'optional' => true,
            'minQty' => 1,
            'maxQty' => 2,
            'sortOrder' => 2,
            'kitItemsProduct' => ['sku_001', 'sku_002'],
        ], [
            'unitCode' => 'item',
            'label' => 'Additional Unit',
            'optional' => false,
            'minQty' => null,
            'maxQty' => null,
            'sortOrder' => 1,
            'kitItemsProduct' => ['sku_001'],
        ]
    ];

    public function __construct(
        private readonly LocalizationManager $localizationManager
    ) {
    }

    #[\Override]
    public function getEntityClass(): string
    {
        return Product::class;
    }

    #[\Override]
    public function getData(): iterable
    {
        return new \ArrayIterator([
            $this->getEntityData(self::PRODUCT_SIMPLE)->current(),
            $this->getEntityData(self::PRODUCT_SIMPLE2)->current(),
            $this->getEntityData(self::PRODUCT_KIT)->current()
        ]);
    }

    #[\Override]
    protected function createEntity($key): Product
    {
        return new Product();
    }

    /**
     * @param string  $key
     * @param Product $entity
     */
    #[\Override]
    public function fillEntityData($key, $entity): void
    {
        $this->fillProduct($key, $entity);

        if ($key === self::PRODUCT_KIT) {
            $this->fillProductKit($entity);
        }
    }

    private function fillProduct(string $key, Product $entity): void
    {
        $localization = $this->localizationManager->getDefaultLocalization();
        $data = self::$productData[$key];

        $name = new ProductName();
        $name->setString($data['name']);

        $localizedName = new ProductName();
        $localizedName->setLocalization($localization)
            ->setString(sprintf('US %s', $data['name']))
            ->setFallback('system');

        $description = new ProductDescription();
        $description->setWysiwyg($data['description']);

        $localizedDescription = new ProductDescription();
        $localizedDescription->setLocalization($localization)
            ->setWysiwyg(sprintf('US %s', $data['description']))
            ->setFallback('system');

        $shortDescription = new ProductShortDescription();
        $shortDescription->setText($data['shortDescription']);

        $localizedShortDescription = new ProductShortDescription();
        $localizedShortDescription->setLocalization($localization)
            ->setText(sprintf('US %s', $data['shortDescription']))
            ->setFallback('system');

        $primaryProductUnit = (new ProductUnit())
            ->setCode($data['primaryUnitCode'])
            ->setDefaultPrecision(3);

        $additionalProductUnit = (new ProductUnit())
            ->setCode($data['additionalUnitCode'])
            ->setDefaultPrecision(0);

        $primaryProductUnitPrecision = new ProductUnitPrecision();
        $this->setEntityId($primaryProductUnitPrecision, 1);
        $primaryProductUnitPrecision
            ->setUnit($primaryProductUnit)
            ->setPrecision($primaryProductUnit->getDefaultPrecision())
            ->setConversionRate(1)
            ->setSell(true);

        $additionalProductUnitPrecision = new ProductUnitPrecision();
        $this->setEntityId($additionalProductUnitPrecision, 2);
        $additionalProductUnitPrecision
            ->setUnit($additionalProductUnit)
            ->setPrecision($additionalProductUnit->getDefaultPrecision())
            ->setConversionRate(5)
            ->setSell(false);

        $attributeFamily = new AttributeFamily();
        $attributeFamily->setCode('default_family');

        $entity->setSku($data['sku'])
            ->setAttributeFamily($attributeFamily)
            ->setStatus($data['status'])
            ->setType($data['type'])
            ->setInventoryStatus($this->createInventoryStatus($data['inventory_status'], $data['inventoryStatusName']))
            ->addName($name)
            ->addName($localizedName)
            ->setPrimaryUnitPrecision($primaryProductUnitPrecision)
            ->addAdditionalUnitPrecision($additionalProductUnitPrecision)
            ->addDescription($description)
            ->addDescription($localizedDescription)
            ->addShortDescription($shortDescription)
            ->addShortDescription($localizedShortDescription);
    }

    private function fillProductKit(Product $entity): void
    {
        foreach (self::$kitItemData as $datum) {
            $kitItem = $this->createKitItem($datum);
            $kitItem->setProductKit($entity);
            $entity->addKitItem($kitItem);
        }
    }

    private function createKitItem(array $data): ProductKitItem
    {
        $productUnit = new ProductUnit();
        $productUnit->setCode($data['unitCode']);
        $productUnit->setDefaultPrecision(0);

        $kitItem = new ProductKitItem();
        $kitItem->setDefaultLabel($data['label']);
        $kitItem->setOptional($data['optional']);
        $kitItem->setMinimumQuantity($data['minQty']);
        $kitItem->setMaximumQuantity($data['maxQty']);
        $kitItem->setSortOrder($data['sortOrder']);
        $kitItem->setProductUnit($productUnit);

        foreach ($data['kitItemsProduct'] as $sku) {
            $productKitItem = new ProductKitItemProduct();
            $productKitItem->setKitItem($kitItem);
            $productKitItem->setProduct((new Product())->setSku($sku));

            $kitItem->addKitItemProduct($productKitItem);
        }

        return $kitItem;
    }

    private function setEntityId(object $entity, ?int $id): void
    {
        $reflectionClass = new \ReflectionClass($entity);
        $method = $reflectionClass->getProperty('id');
        $method->setValue($entity, $id);
    }

    private function createInventoryStatus(string $id, string $name): EnumOptionInterface
    {
        return new EnumOption(
            Product::INVENTORY_STATUS_ENUM_CODE,
            $name,
            $id
        );
    }
}
