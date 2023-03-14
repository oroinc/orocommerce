<?php

namespace Oro\Bundle\InventoryBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\InventoryBundle\Provider\UpcomingProductProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

class LoadProductUpcomingData extends AbstractFixture implements DependentFixtureInterface
{
    const CATEGORIES = [
        LoadCategoryData::SECOND_LEVEL2 => [
            UpcomingProductProvider::IS_UPCOMING => true,
            UpcomingProductProvider::AVAILABILITY_DATE => '2050-10-10'
        ],
        LoadCategoryData::THIRD_LEVEL2 => [
            UpcomingProductProvider::IS_UPCOMING => true,
            UpcomingProductProvider::AVAILABILITY_DATE => '2000-10-10'
        ]
    ];

    const PRODUCTS = [
        LoadProductData::PRODUCT_1 => [
            UpcomingProductProvider::IS_UPCOMING => true,
            UpcomingProductProvider::AVAILABILITY_DATE => '2070-10-10'
        ],
        LoadProductData::PRODUCT_2 => [
            UpcomingProductProvider::IS_UPCOMING => false,
            UpcomingProductProvider::AVAILABILITY_DATE => '2080-10-10'
        ],
        LoadProductData::PRODUCT_3 => [
            UpcomingProductProvider::IS_UPCOMING => true,
            UpcomingProductProvider::AVAILABILITY_DATE => '1900-10-10'
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadCategoryProductData::class];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        foreach (self::CATEGORIES as $categoryReference => $values) {
            /** @var Category $category */
            $category = $this->getReference($categoryReference);
            $fallbackEntity = $this->createFallbackEntity($values[UpcomingProductProvider::IS_UPCOMING]);
            $manager->persist($fallbackEntity);
            $accessor->setValue($category, UpcomingProductProvider::IS_UPCOMING, $fallbackEntity);
            $date = new \DateTime($values[UpcomingProductProvider::AVAILABILITY_DATE]);
            $accessor->setValue($category, UpcomingProductProvider::AVAILABILITY_DATE, $date);
        }

        foreach (self::PRODUCTS as $productReference => $values) {
            /** @var Product $product */
            $product = $this->getReference($productReference);
            $fallbackEntity = $this->createFallbackEntity($values[UpcomingProductProvider::IS_UPCOMING]);
            $manager->persist($fallbackEntity);
            $accessor->setValue($product, UpcomingProductProvider::IS_UPCOMING, $fallbackEntity);
            $date = new \DateTime($values[UpcomingProductProvider::AVAILABILITY_DATE]);
            $accessor->setValue($product, UpcomingProductProvider::AVAILABILITY_DATE, $date);
        }

        $manager->flush();
    }

    /**
     * @param $value
     * @return EntityFieldFallbackValue
     */
    protected function createFallbackEntity($value)
    {
        $entityFallback = new EntityFieldFallbackValue();
        $entityFallback->setScalarValue($value);
        return $entityFallback;
    }
}
