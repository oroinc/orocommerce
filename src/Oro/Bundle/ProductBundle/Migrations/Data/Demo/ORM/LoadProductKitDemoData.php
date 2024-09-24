<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemLabel;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemProduct;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Symfony\Component\Yaml\Yaml;

/**
 * Loads demo product kits with images.
 */
class LoadProductKitDemoData extends LoadProductDemoData
{
    /** @var Product[] */
    private array $products = [];

    #[\Override]
    public function getDependencies(): array
    {
        return array_merge(parent::getDependencies(), [
            LoadProductUnitPrecisionDemoData::class,
        ]);
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        parent::load($manager);
        $this->products = [];
    }

    #[\Override]
    protected function getProducts(): \Iterator
    {
        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroProductBundle/Migrations/Data/Demo/ORM/data/product_kits.yaml');
        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        return new \ArrayIterator(Yaml::parseFile($filePath));
    }

    #[\Override]
    protected function applyAdditionalData(Product $product, array $row, ObjectManager $manager): void
    {
        foreach ($row['units'] ?? [] as $additionalUnit) {
            $productUnit = $this->getProductUnit($manager, $additionalUnit['unit']);
            $productUnitPrecision = new ProductUnitPrecision();
            $productUnitPrecision
                ->setProduct($product)
                ->setUnit($productUnit)
                ->setPrecision((int) $additionalUnit['precision'])
                ->setConversionRate((float) $additionalUnit['conversion_rate'])
                ->setSell((bool) $additionalUnit['sell']);
            $manager->persist($productUnitPrecision);
        }

        foreach ($row['kitItems'] as $kitItem) {
            $this->addProductKitItem($manager, $product, $kitItem);
        }
    }

    private function addProductKitItem(ObjectManager $manager, Product $productKit, array $kitItemData): void
    {
        $kitItem = (new ProductKitItem())
            ->addLabel((new ProductKitItemLabel())->setString($kitItemData['label']))
            ->setOptional($kitItemData['optional'])
            ->setSortOrder($kitItemData['sortOrder'])
            ->setMinimumQuantity($kitItemData['minimumQuantity'])
            ->setMaximumQuantity($kitItemData['maximumQuantity'])
            ->setProductUnit($this->getProductUnit($manager, $kitItemData['unit']));

        foreach ($kitItemData['products'] as $kitItemProductSku) {
            $kitItem->addKitItemProduct(
                (new ProductKitItemProduct())
                    ->setProduct($this->getProduct($manager, $kitItemProductSku))
            );
        }

        $productKit->addKitItem($kitItem);
    }

    private function getProduct(ObjectManager $manager, string $sku): ?Product
    {
        if (!array_key_exists($sku, $this->products)) {
            $this->products[$sku] = $manager->getRepository(Product::class)->findOneBy(['sku' => $sku]);
        }

        return $this->products[$sku];
    }
}
