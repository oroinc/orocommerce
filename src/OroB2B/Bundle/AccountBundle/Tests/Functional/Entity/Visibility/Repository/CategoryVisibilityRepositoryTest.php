<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\Visibility\Repository;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\Repository\CategoryVisibilityRepository;
use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;

/**
 * @dbIsolation
 */
class CategoryVisibilityRepositoryTest extends CategoryVisibilityTestCase
{
    /**
     * @var CategoryVisibilityRepository
     */
    protected $repository;

    /**
     * {@inheritdoc}
     */
    protected function getRepositoryName()
    {
        return 'OroB2BAccountBundle:Visibility\CategoryVisibility';
    }

    /**
     * @dataProvider getCategoriesVisibilitiesQueryBuilderDataProvider
     * @param array $expectedData
     */
    public function testGetCategoriesVisibilitiesQueryBuilder(array $expectedData)
    {
        /** @var array $actualData */
        $actualData = $this->repository->getCategoriesVisibilitiesQueryBuilder()->addOrderBy('c.left')
            ->getQuery()->execute();

        $this->assertVisibilities($expectedData, $actualData);
    }

    /**
     * @return array
     */
    public function getCategoriesVisibilitiesQueryBuilderDataProvider()
    {
        return [
            [
                'expectedData' => [
                    [
                        'category' => self::ROOT_CATEGORY,
                        'visibility' => null,
                        'category_parent' => null,
                    ],
                    [
                        'category' => LoadCategoryData::FIRST_LEVEL,
                        'visibility' => CategoryVisibility::VISIBLE,
                        'category_parent' => self::ROOT_CATEGORY,
                    ],
                    [
                        'category' => LoadCategoryData::SECOND_LEVEL1,
                        'visibility' => null,
                        'category_parent' => LoadCategoryData::FIRST_LEVEL,
                    ],
                    [
                        'category' => LoadCategoryData::SECOND_LEVEL2,
                        'visibility' => null,
                        'category_parent' => LoadCategoryData::FIRST_LEVEL,
                    ],
                    [
                        'category' => LoadCategoryData::THIRD_LEVEL1,
                        'visibility' => CategoryVisibility::VISIBLE,
                        'category_parent' => LoadCategoryData::SECOND_LEVEL1,
                    ],
                    [
                        'category' => LoadCategoryData::THIRD_LEVEL2,
                        'visibility' => CategoryVisibility::HIDDEN,
                        'category_parent' => LoadCategoryData::SECOND_LEVEL2,
                    ],
                    [
                        'category' => LoadCategoryData::FOURTH_LEVEL1,
                        'visibility' => null,
                        'category_parent' => LoadCategoryData::THIRD_LEVEL1,
                    ],
                    [
                        'category' => LoadCategoryData::FOURTH_LEVEL2,
                        'visibility' => null,
                        'category_parent' => LoadCategoryData::THIRD_LEVEL2,
                    ]
                ]
            ]
        ];
    }
}
