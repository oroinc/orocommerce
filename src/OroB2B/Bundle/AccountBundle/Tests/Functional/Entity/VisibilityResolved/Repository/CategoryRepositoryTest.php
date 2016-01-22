<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\VisibilityResolved\Repository;

use Oro\Component\Testing\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\CategoryRepository;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

/**
 * @dbIsolation
 */
class CategoryRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(['OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadCategoryVisibilityData']);
        // TODO: remove cache generation in scope of BB-1803
        $this->getContainer()->get('orob2b_account.visibility.cache.product.category.cache_builder')->buildCache();
    }

    /**
     * @dataProvider isCategoryVisibleDataProvider
     * @param string $categoryName
     * @param int $configValue
     * @param bool $expectedVisibility
     */
    public function testIsCategoryVisible($categoryName, $configValue, $expectedVisibility)
    {
        /** @var Category $category */
        $category = $this->getReference($categoryName);

        $actualVisibility = $this->getRepository()->isCategoryVisible($category, $configValue);

        $this->assertEquals($expectedVisibility, $actualVisibility);
    }

    /**
     * @return array
     */
    public function isCategoryVisibleDataProvider()
    {
        return [
            [
                'categoryName' => 'category_1',
                'configValue' => 1,
                'expectedVisibility' => true,
            ],
            [
                'categoryName' => 'category_1_2',
                'configValue' => 1,
                'expectedVisibility' => true,
            ],
            [
                'categoryName' => 'category_1_2_3',
                'configValue' => 1,
                'expectedVisibility' => true,
            ],
        ];
    }

    /**
     * @dataProvider getCategoryIdsByVisibilityDataProvider
     * @param int $visibility
     * @param int $configValue
     * @param array $expected
     */
    public function testGetCategoryIdsByVisibility($visibility, $configValue, array $expected)
    {
        $categoryIds = $this->getRepository()->getCategoryIdsByVisibility($visibility, $configValue);

        $expectedCategoryIds = [];
        foreach ($expected as $categoryName) {
            /** @var Category $category */
            $category = $this->getReference($categoryName);
            $expectedCategoryIds[] = $category->getId();
        }

        if ($visibility == BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE) {
            $masterCatalogId = $this->getMasterCatalog()->getId();
            array_unshift($expectedCategoryIds, $masterCatalogId);
        }

        $this->assertEquals($expectedCategoryIds, $categoryIds);
    }

    /**
     * @return array
     */
    public function getCategoryIdsByVisibilityDataProvider()
    {
        return [
            [
                'visibility' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'configValue' => 1,
                'expected' => [
                    'category_1',
                    'category_1_2',
                    'category_1_2_3',
                    'category_1_2_3_4',
                    'category_1_5',
                ]
            ],
            [
                'visibility' => BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                'configValue' => 1,
                'expected' => [
                    'category_1_5_6',
                    'category_1_5_6_7',
                ]
            ],
        ];
    }

    /**
     * @return CategoryRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\CategoryVisibilityResolved')
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\CategoryVisibilityResolved');
    }

    /**
     * @return Category
     */
    protected function getMasterCatalog()
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass('OroB2BCatalogBundle:Category')
            ->getRepository('OroB2BCatalogBundle:Category')
            ->getMasterCatalogRoot();
    }
}
