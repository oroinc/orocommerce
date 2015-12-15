<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\Visibility\Repository;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\Repository\AccountGroupCategoryVisibilityRepository;
use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;

/**
 * @dbIsolation
 */
class AccountGroupCategoryVisibilityRepositoryTest extends CategoryVisibilityTestCase
{
    /**
     * @var AccountGroupCategoryVisibilityRepository
     */
    protected $repository;

    /**
     * @inheritDoc
     */
    protected function getRepositoryName()
    {
        return 'OroB2BAccountBundle:Visibility\AccountGroupCategoryVisibility';
    }

    /**
     * @dataProvider getCategoryWithVisibilitiesForAccountGroupDataProvider
     * @param string $accountGroupReference
     * @param array $expectedData
     */
    public function testGetCategoryWithVisibilitiesForAccountGroup($accountGroupReference, array $expectedData)
    {
        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->getReference($accountGroupReference);
        /** @var array $actualData */
        $actualData = $this->repository->getCategoryWithVisibilitiesForAccountGroup($accountGroup)
            ->addOrderBy('c.left')
            ->getQuery()->execute();

        $this->assertVisibilities($expectedData, $actualData, ['account_group_visibility']);
    }

    /**
     * @return array
     */
    public function getCategoryWithVisibilitiesForAccountGroupDataProvider()
    {
        return [
            [
                'accountGroupReference' => 'account_group.group1',
                'expectedData' => [
                    [
                        'category' => self::ROOT_CATEGORY,
                        'category_parent' => null,
                        'visibility' => null,
                        'account_group_visibility' => null,
                    ],
                    [
                        'category' => LoadCategoryData::FIRST_LEVEL,
                        'category_parent' => self::ROOT_CATEGORY,
                        'visibility' => CategoryVisibility::VISIBLE,
                        'account_group_visibility' => AccountGroupCategoryVisibility::HIDDEN,
                    ],
                    [
                        'category' => LoadCategoryData::SECOND_LEVEL1,
                        'category_parent' => LoadCategoryData::FIRST_LEVEL,
                        'visibility' => null,
                        'account_group_visibility' => AccountGroupCategoryVisibility::PARENT_CATEGORY,
                    ],
                    [
                        'category' => LoadCategoryData::SECOND_LEVEL2,
                        'category_parent' => LoadCategoryData::FIRST_LEVEL,
                        'visibility' => null,
                        'account_group_visibility' => null,
                    ],
                    [
                        'category' => LoadCategoryData::THIRD_LEVEL1,
                        'category_parent' => LoadCategoryData::SECOND_LEVEL1,
                        'visibility' => CategoryVisibility::VISIBLE,
                        'account_group_visibility' => AccountGroupCategoryVisibility::PARENT_CATEGORY,
                    ],
                    [
                        'category' => LoadCategoryData::THIRD_LEVEL2,
                        'category_parent' => LoadCategoryData::SECOND_LEVEL2,
                        'visibility' => CategoryVisibility::HIDDEN,
                        'account_group_visibility' => AccountGroupCategoryVisibility::PARENT_CATEGORY,
                    ],
                    [
                        'category' => LoadCategoryData::FOURTH_LEVEL1,
                        'category_parent' => LoadCategoryData::THIRD_LEVEL1,
                        'visibility' => null,
                        'account_group_visibility' => AccountGroupCategoryVisibility::PARENT_CATEGORY,
                    ],
                    [
                        'category' => LoadCategoryData::FOURTH_LEVEL2,
                        'category_parent' => LoadCategoryData::THIRD_LEVEL2,
                        'visibility' => null,
                        'account_group_visibility' => AccountGroupCategoryVisibility::PARENT_CATEGORY,
                    ]
                ]
            ]
        ];
    }
}
