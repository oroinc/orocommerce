<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\CatalogBundle\Entity\Category;

class LoadCategoryProductData extends AbstractFixture implements DependentFixtureInterface
{
    protected static $relations = [
        LoadCategoryData::FIRST_LEVEL => LoadProductData::TEST_PRODUCT_01,
        LoadCategoryData::SECOND_LEVEL1 => LoadProductData::TEST_PRODUCT_02,
        LoadCategoryData::THIRD_LEVEL1 => LoadProductData::TEST_PRODUCT_03,
        LoadCategoryData::THIRD_LEVEL2 => LoadProductData::TEST_PRODUCT_04,
    ];

    /**
     * @return array
     */
    public static function getRelations()
    {
        return self::$relations;
    }

    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData',
            'OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadProductData',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $parent = $this->getReference(LoadCategoryData::FIRST_LEVEL);
        $children1 = $this->getReference(LoadCategoryData::SECOND_LEVEL1);
        $children2 = $this->getReference(LoadCategoryData::THIRD_LEVEL1);
        $children3 = $this->getReference(LoadCategoryData::THIRD_LEVEL2);

        $parent->addProduct($this->getReference(self::$relations[LoadCategoryData::FIRST_LEVEL]));
        $children1->addProduct($this->getReference(self::$relations[LoadCategoryData::SECOND_LEVEL1]));
        $children2->addProduct($this->getReference(self::$relations[LoadCategoryData::THIRD_LEVEL1]));
        $children3->addProduct($this->getReference(self::$relations[LoadCategoryData::THIRD_LEVEL2]));

        $manager->flush();
    }

    /**
     * @param $categories Category[]
     * @param $title string
     * @return Category|null
     */
    protected function findCategoryByTitle($categories, $title)
    {
        foreach ($categories as $category) {
            if ($category->getDefaultTitle()->getString() == $title) {
                return $category;
            }
        }

        return null;
    }
}
