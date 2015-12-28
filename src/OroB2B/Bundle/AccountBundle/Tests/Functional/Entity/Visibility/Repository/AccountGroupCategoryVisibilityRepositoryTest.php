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
     * {@inheritdoc}
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

    /**
     * @param array $expectedVisibilities
     * @dataProvider getParentCategoryVisibilitiesDataProvider
     */
    public function testGetParentCategoryVisibilities(array $expectedVisibilities)
    {
        $this->assertEquals(
            $this->convertReferences($expectedVisibilities),
            $this->repository->getParentCategoryVisibilities()
        );
    }

    /**
     * @return array
     */
    public function getParentCategoryVisibilitiesDataProvider()
    {
        return [
            'all parent category visibilities' => [[
                [
                    'visibility_id' => 'category_1.visibility.account_group.group3',
                    'parent_visibility_id' => null,
                    'parent_visibility' => null,
                    'category_id' => 'category_1',
                    'parent_category_id' => self::ROOT_CATEGORY,
                ],
                [
                    'visibility_id' => 'category_1_2.visibility.account_group.group1',
                    'parent_visibility_id' => 'category_1.visibility.account_group.group1',
                    'parent_visibility' => AccountGroupCategoryVisibility::HIDDEN,
                    'category_id' => 'category_1_2',
                    'parent_category_id' => 'category_1',
                ],
                [
                    'visibility_id' => 'category_1_2.visibility.account_group.group2',
                    'parent_visibility_id' => null,
                    'parent_visibility' => null,
                    'category_id' => 'category_1_2',
                    'parent_category_id' => 'category_1',
                ],
                [
                    'visibility_id' => 'category_1_5.visibility.account_group.group3',
                    'parent_visibility_id' => 'category_1.visibility.account_group.group3',
                    'parent_visibility' => AccountGroupCategoryVisibility::PARENT_CATEGORY,
                    'category_id' => 'category_1_5',
                    'parent_category_id' => 'category_1',
                ],
                [
                    'visibility_id' => 'category_1_2_3.visibility.account_group.group1',
                    'parent_visibility_id' => 'category_1_2.visibility.account_group.group1',
                    'parent_visibility' => AccountGroupCategoryVisibility::PARENT_CATEGORY,
                    'category_id' => 'category_1_2_3',
                    'parent_category_id' => 'category_1_2',
                ],
                [
                    'visibility_id' => 'category_1_5_6.visibility.account_group.group1',
                    'parent_visibility_id' => null,
                    'parent_visibility' => null,
                    'category_id' => 'category_1_5_6',
                    'parent_category_id' => 'category_1_5',
                ],
                [
                    'visibility_id' => 'category_1_5_6.visibility.account_group.group3',
                    'parent_visibility_id' => 'category_1_5.visibility.account_group.group3',
                    'parent_visibility' => AccountGroupCategoryVisibility::PARENT_CATEGORY,
                    'category_id' => 'category_1_5_6',
                    'parent_category_id' => 'category_1_5',
                ],
                [
                    'visibility_id' => 'category_1_2_3_4.visibility.account_group.group3',
                    'parent_visibility_id' => 'category_1_2_3.visibility.account_group.group3',
                    'parent_visibility' => AccountGroupCategoryVisibility::HIDDEN,
                    'category_id' => 'category_1_2_3_4',
                    'parent_category_id' => 'category_1_2_3',
                ],
                [
                    'visibility_id' => 'category_1_2_3_4.visibility.account_group.group1',
                    'parent_visibility_id' => 'category_1_2_3.visibility.account_group.group1',
                    'parent_visibility' => AccountGroupCategoryVisibility::PARENT_CATEGORY,
                    'category_id' => 'category_1_2_3_4',
                    'parent_category_id' => 'category_1_2_3',
                ],
                [
                    'visibility_id' => 'category_1_5_6_7.visibility.account_group.group1',
                    'parent_visibility_id' => 'category_1_5_6.visibility.account_group.group1',
                    'parent_visibility' => AccountGroupCategoryVisibility::PARENT_CATEGORY,
                    'category_id' => 'category_1_5_6_7',
                    'parent_category_id' => 'category_1_5_6',
                ],
                [
                    'visibility_id' => 'category_1_5_6_7.visibility.account_group.group3',
                    'parent_visibility_id' => 'category_1_5_6.visibility.account_group.group3',
                    'parent_visibility' => AccountGroupCategoryVisibility::PARENT_CATEGORY,
                    'category_id' => 'category_1_5_6_7',
                    'parent_category_id' => 'category_1_5_6',
                ],
            ]]
        ];
    }

    /**
     * @param array $data
     * @return array
     */
    protected function convertReferences(array $data)
    {
        foreach ($data as $key => $row) {
            if (is_string($row['visibility_id'])) {
                $data[$key]['visibility_id'] = $this->getVisibilityId($row['visibility_id']);
            }
            if (is_string($row['parent_visibility_id'])) {
                $data[$key]['parent_visibility_id'] = $this->getVisibilityId($row['parent_visibility_id']);
            }
            if (is_string($row['category_id'])) {
                $data[$key]['category_id'] = $this->getCategoryId($row['category_id']);
            }
            if (is_string($row['parent_category_id'])) {
                $data[$key]['parent_category_id'] = $this->getCategoryId($row['parent_category_id']);
            }
        }
        return $data;
    }

    /**
     * @param string $reference
     * @return int
     */
    protected function getVisibilityId($reference)
    {
        /** @var AccountGroupCategoryVisibility $visibility */
        $visibility = $this->getReference($reference);

        return $visibility->getId();
    }
}
