<?php

namespace Oro\Bundle\InventoryBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Fallback\Provider\CategoryFallbackProvider;
use Oro\Bundle\CatalogBundle\Migrations\Data\Demo\ORM\LoadProductCategoryDemoData;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Fallback\Provider\SystemConfigFallbackProvider;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\InventoryBundle\Inventory\LowInventoryProvider;
use Oro\Bundle\MigrationBundle\Fixture\AbstractEntityReferenceFixture;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Fixture to load fallback fields data.
 */
class LoadFallbackFieldsData extends AbstractEntityReferenceFixture implements DependentFixtureInterface
{
    const FALLBACK_FIELDS = [
        'minimumQuantityToOrder',
        'maximumQuantityToOrder',
        'manageInventory',
        LowInventoryProvider::HIGHLIGHT_LOW_INVENTORY_OPTION,
        'inventoryThreshold',
        LowInventoryProvider::LOW_INVENTORY_THRESHOLD_OPTION,
        'decrementQuantity',
        'backOrder',
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadProductCategoryDemoData::class];
    }

    /**
     * @inheritDoc
     */
    public function load(ObjectManager $manager)
    {
        /** @var CategoryRepository $categoryRepository */
        $categoryRepository = $manager->getRepository('OroCatalogBundle:Category');

        /** @var Category[] $categories */
        $categories = $categoryRepository->findAll();
        foreach ($categories as $category) {
            $this->addFallbacksToEntity($manager, SystemConfigFallbackProvider::FALLBACK_ID, $category);
        }

        /** @var Product[] $products */
        $products = $manager->getRepository('OroProductBundle:Product')->findAll();
        foreach ($products as $product) {
            $category = $categoryRepository->findOneByProduct($product);
            if ($category) {
                $this->addFallbacksToEntity($manager, CategoryFallbackProvider::FALLBACK_ID, $product);
            } else {
                $this->addFallbacksToEntity($manager, SystemConfigFallbackProvider::FALLBACK_ID, $product);
            }
        }

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param $fallbackId
     * @param $entity
     * @return mixed
     */
    protected function addFallbacksToEntity(ObjectManager $manager, $fallbackId, $entity)
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        foreach (self::FALLBACK_FIELDS as $fallbackField) {
            $fallbackEntity = $this->createFallbackEntity($manager, $fallbackId);
            $accessor->setValue($entity, $fallbackField, $fallbackEntity);
        }

        return $entity;
    }

    /**
     * @param ObjectManager $manager
     * @param string $fallbackId
     * @return EntityFieldFallbackValue
     */
    protected function createFallbackEntity(ObjectManager $manager, $fallbackId)
    {
        $entityFallback = new EntityFieldFallbackValue();
        $entityFallback->setFallback($fallbackId);
        $manager->persist($entityFallback);

        return $entityFallback;
    }
}
