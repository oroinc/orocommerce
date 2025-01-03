<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

class LoadCategoryProductData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    protected static $relations = [
        LoadCategoryData::FIRST_LEVEL   => [LoadProductData::PRODUCT_1],
        LoadCategoryData::SECOND_LEVEL1 => [LoadProductData::PRODUCT_2],
        LoadCategoryData::SECOND_LEVEL2 => [LoadProductData::PRODUCT_5],
        LoadCategoryData::THIRD_LEVEL1  => [LoadProductData::PRODUCT_3],
        LoadCategoryData::THIRD_LEVEL2  => [LoadProductData::PRODUCT_4],
        LoadCategoryData::FOURTH_LEVEL1 => [LoadProductData::PRODUCT_6],
        LoadCategoryData::FOURTH_LEVEL2 => [LoadProductData::PRODUCT_7, LoadProductData::PRODUCT_8],
    ];

    protected static $categorySortOrder = [
        LoadProductData::PRODUCT_2 => 1,
        LoadProductData::PRODUCT_5 => 0.2
    ];

    #[\Override]
    public function getDependencies()
    {
        return [
            LoadCategoryData::class,
            LoadProductData::class,
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager)
    {
        foreach (self::$relations as $categoryReference => $productsReference) {
            foreach ($productsReference as $productReference) {
                if (array_key_exists($productReference, self::$categorySortOrder)) {
                    $this->getReference($productReference)
                        ->setCategorySortOrder(self::$categorySortOrder[$productReference]);
                }
                $this->getReference($categoryReference)
                    ->addProduct($this->getReference($productReference));
            }
        }

        $manager->flush();
    }
}
