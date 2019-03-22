<?php

namespace Oro\Bundle\InventoryBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\InventoryBundle\Provider\ProductUpcomingProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Symfony\Component\PropertyAccess\PropertyAccess;

class LoadProductUpcomingData extends AbstractFixture implements DependentFixtureInterface
{
    const CATEGORIES = [
        LoadCategoryData::SECOND_LEVEL2 => [
            ProductUpcomingProvider::IS_UPCOMING => true,
            ProductUpcomingProvider::AVAILABILITY_DATE => '2050-10-10'
        ],
        LoadCategoryData::THIRD_LEVEL2 => [
            ProductUpcomingProvider::IS_UPCOMING => true,
            ProductUpcomingProvider::AVAILABILITY_DATE => '2000-10-10'
        ]
    ];

    const PRODUCTS = [
        LoadProductData::PRODUCT_1 => [
            ProductUpcomingProvider::IS_UPCOMING => true,
            ProductUpcomingProvider::AVAILABILITY_DATE => '2070-10-10'
        ],
        LoadProductData::PRODUCT_2 => [
            ProductUpcomingProvider::IS_UPCOMING => false,
            ProductUpcomingProvider::AVAILABILITY_DATE => '2080-10-10'
        ],
        LoadProductData::PRODUCT_3 => [
            ProductUpcomingProvider::IS_UPCOMING => true,
            ProductUpcomingProvider::AVAILABILITY_DATE => '1900-10-10'
        ],
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
            $fallbackEntity = $this->createFallbackEntity($values[ProductUpcomingProvider::IS_UPCOMING]);
            $manager->persist($fallbackEntity);
            $accessor->setValue($category, ProductUpcomingProvider::IS_UPCOMING, $fallbackEntity);
            $date = new \DateTime($values[ProductUpcomingProvider::AVAILABILITY_DATE]);
            $accessor->setValue($category, ProductUpcomingProvider::AVAILABILITY_DATE, $date);
        }

        foreach (self::PRODUCTS as $productReference => $values) {
            /** @var Product $product */
            $product = $this->getReference($productReference);
            $fallbackEntity = $this->createFallbackEntity($values[ProductUpcomingProvider::IS_UPCOMING]);
            $manager->persist($fallbackEntity);
            $accessor->setValue($product, ProductUpcomingProvider::IS_UPCOMING, $fallbackEntity);
            $date = new \DateTime($values[ProductUpcomingProvider::AVAILABILITY_DATE]);
            $accessor->setValue($product, ProductUpcomingProvider::AVAILABILITY_DATE, $date);
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
