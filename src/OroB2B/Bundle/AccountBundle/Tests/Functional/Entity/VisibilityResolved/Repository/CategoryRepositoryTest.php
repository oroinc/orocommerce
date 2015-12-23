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

        $this->loadFixtures([
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadCategoryVisibilityResolvedData'
        ]);
    }

    /**
     * @dataProvider isCategoryVisibleDataProvider
     * @param string $categoryName
     * @param bool $expectedVisibility
     */
    public function testIsCategoryVisible($categoryName, $expectedVisibility)
    {
        /** @var Category $category */
        $category = $this->getReference($categoryName);

        $actualVisibility = $this->getRepository()->isCategoryVisible($category);

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
                'expectedVisibility' => true,
            ],
            [
                'categoryName' => 'category_1_2',
                'expectedVisibility' => false,
            ],
            [
                'categoryName' => 'category_1_2_3',
                'expectedVisibility' => true,
            ],
        ];
    }

    /**
     * @dataProvider getCategoryIdsByVisibilityDataProvider
     * @param int $visibility
     * @param array $expected
     */
    public function testGetCategoryIdsByVisibility($visibility, array $expected)
    {
        $categoryIds = $this->getRepository()->getCategoryIdsByVisibility($visibility);

        $expectedCategoryIds = [];
        foreach ($expected as $categoryName) {
            /** @var Category $category */
            $category = $this->getReference($categoryName);
            $expectedCategoryIds[] = $category->getId();
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
                'expected' => [
                    'category_1',
                    'category_1_2_3',
                ]
            ],
            [
                'visibility' => BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                'expected' => [
                    'category_1_2',
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
}
