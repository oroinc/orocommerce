<?php

namespace Oro\Bundle\InventoryBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Entity\Repository\InventoryLevelRepository;
use Oro\Bundle\MigrationBundle\Fixture\AbstractEntityReferenceFixture;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;
use Symfony\Component\Yaml\Yaml;

class UpdateInventoryLevelsQuantities extends AbstractEntityReferenceFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadProductUnitPrecisions::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var InventoryLevelRepository $inventoryRepository */
        $inventoryRepository = $manager->getRepository(InventoryLevel::class);

        $productInventoryData = $this->getInventoryLevelUpdateData();
        foreach ($productInventoryData as $productKey => $data) {
            foreach ($data as $item) {
                if (isset($item['isPrimary'])) {
                    $item['unit'] = $this->getReference($productKey)->getPrimaryUnitPrecision()->getProductUnitCode();
                }

                $inventoryLevel = $inventoryRepository->getLevelByProductAndProductUnit(
                    $this->getReference($productKey),
                    $this->getReference('product_unit.' . $item['unit'])
                );

                $inventoryLevel->setQuantity($item['quantity']);

                $manager->persist($inventoryLevel);
                $this->addReference($item['reference'], $inventoryLevel);
            }
        }

        $manager->flush();
    }

    /**
     * Group data in array with product sku as key and inventory data as value
     *
     * @param array $data Array containing inventory data fixtures for update
     * @return array
     */
    protected function getProductInventory($data)
    {
        $productInventory = [];

        foreach ($data as $ref => $inventoryData) {
            $productKey = $inventoryData['product'];
            if (!isset($productKey) || empty($productKey)) {
                continue;
            }

            if (!isset($productInventory[$productKey])) {
                $productInventory[$productKey] = [];
            }

            $inventoryData['reference'] = $ref;
            $productInventory[$productKey][] = $inventoryData;
        }

        return $productInventory;
    }

    /**
     * @return array
     */
    protected function getInventoryLevelUpdateData()
    {
        $filePath = __DIR__ . DIRECTORY_SEPARATOR . 'inventory_level.yml';

        $data = Yaml::parse(file_get_contents($filePath));

        return $this->getProductInventory($data);
    }
}
