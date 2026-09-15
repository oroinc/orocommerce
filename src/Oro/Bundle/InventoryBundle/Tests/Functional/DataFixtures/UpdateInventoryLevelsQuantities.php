<?php

namespace Oro\Bundle\InventoryBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Entity\Repository\InventoryLevelRepository;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;

class UpdateInventoryLevelsQuantities extends AbstractFixture implements DependentFixtureInterface
{
    #[\Override]
    public function getDependencies()
    {
        return [
            LoadProductUnitPrecisions::class
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager)
    {
        /** @var InventoryLevelRepository $inventoryRepository */
        $inventoryRepository = $manager->getRepository(InventoryLevel::class);
        $inventoryLevelData = $this->getInventoryLevelData();
        foreach ($inventoryLevelData as $productReference => $data) {
            /** @var Product $product */
            $product = $this->getReference($productReference);
            foreach ($data as $item) {
                $isPrimary = $item['isPrimary'] ?? false;
                $productUnitCode = $isPrimary
                    ? $product->getPrimaryUnitPrecision()->getProductUnitCode()
                    : $item['unit'];

                /** @var ProductUnit $productUnit */
                $productUnit = $this->getReference('product_unit.' . $productUnitCode);
                $inventoryLevel = $inventoryRepository->getLevelByProductAndProductUnit($product, $productUnit);
                if (null === $inventoryLevel) {
                    throw new \RuntimeException(\sprintf(
                        'The inventory level was not found. Product: %s. Unit: %s.',
                        $productReference,
                        $productUnitCode
                    ));
                }
                $inventoryLevel->setQuantity($item['quantity']);
                $manager->persist($inventoryLevel);
                $inventoryLevelReference = \sprintf(
                    'inventory_level.product_unit_precision.%s.%s',
                    $productReference,
                    $isPrimary ? 'primary_unit' : $productUnitCode
                );
                $this->addReference($inventoryLevelReference, $inventoryLevel);
            }
        }

        $manager->flush();
    }

    protected function getInventoryLevelData(): array
    {
        return [
            'product-1' => [
                ['unit' => 'liter', 'quantity' => 10],
                ['unit' => 'bottle', 'quantity' => 99],
                ['isPrimary' => true, 'quantity' => 10]
            ],
            'product-2' => [
                ['unit' => 'liter', 'quantity' => 12.345],
                ['unit' => 'milliliter', 'quantity' => 10],
                ['unit' => 'bottle', 'quantity' => 98],
                ['unit' => 'box', 'quantity' => 42]
            ]
        ];
    }
}
