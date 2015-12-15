<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\Visibility\Repository;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\Repository\AccountCategoryVisibilityRepository;
use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;

/**
 * @dbIsolation
 */
class AccountCategoryVisibilityRepositoryTest extends CategoryVisibilityTestCase
{
    /**
     * @var AccountCategoryVisibilityRepository
     */
    protected $repository;

    /**
     * @inheritDoc
     */
    protected function getRepositoryName()
    {
        return 'OroB2BAccountBundle:Visibility\AccountCategoryVisibility';
    }

    /**
     * @dataProvider getCategoryVisibilitiesForAccountDataProvider
     * @param $accountReference
     * @param array $expectedData
     */
    public function testGetCategoryVisibilitiesForAccount($accountReference, array $expectedData)
    {
        /** @var Account $account */
        $account = $this->getReference($accountReference);
        /** @var array $actualData */
        $actualData = $this->repository->getCategoryVisibilitiesForAccount($account)->addOrderBy('c.left')
            ->getQuery()->execute();

        $this->assertVisibilities($expectedData, $actualData, ['account_visibility', 'account_group_visibility']);
    }

    /**
     * @return array
     */
    public function getCategoryVisibilitiesForAccountDataProvider()
    {
        return [
            [
                'accountReference' => 'account.level_1',
                'expectedData' => [
                    [
                        'category' => self::ROOT_CATEGORY,
                        'category_parent' => null,
                        'visibility' => null,
                        'account_group_visibility' => null,
                        'account_visibility' => null,
                    ],
                    [
                        'category' => LoadCategoryData::FIRST_LEVEL,
                        'category_parent' => self::ROOT_CATEGORY,
                        'visibility' => CategoryVisibility::VISIBLE,
                        'account_group_visibility' => AccountGroupCategoryVisibility::HIDDEN,
                        'account_visibility' => AccountCategoryVisibility::PARENT_CATEGORY,
                    ],
                    [
                        'category' => LoadCategoryData::SECOND_LEVEL1,
                        'category_parent' => LoadCategoryData::FIRST_LEVEL,
                        'visibility' => null,
                        'account_group_visibility' => AccountGroupCategoryVisibility::PARENT_CATEGORY,
                        'account_visibility' => null,
                    ],
                    [
                        'category' => LoadCategoryData::SECOND_LEVEL2,
                        'category_parent' => LoadCategoryData::FIRST_LEVEL,
                        'visibility' => null,
                        'account_group_visibility' => null,
                        'account_visibility' => null,
                    ],
                    [
                        'category' => LoadCategoryData::THIRD_LEVEL1,
                        'category_parent' => LoadCategoryData::SECOND_LEVEL1,
                        'visibility' => CategoryVisibility::VISIBLE,
                        'account_group_visibility' => AccountGroupCategoryVisibility::PARENT_CATEGORY,
                        'account_visibility' => null,
                    ],
                    [
                        'category' => LoadCategoryData::THIRD_LEVEL2,
                        'category_parent' => LoadCategoryData::SECOND_LEVEL2,
                        'visibility' => CategoryVisibility::HIDDEN,
                        'account_group_visibility' => AccountGroupCategoryVisibility::PARENT_CATEGORY,
                        'account_visibility' => AccountCategoryVisibility::CATEGORY,
                    ],
                    [
                        'category' => LoadCategoryData::FOURTH_LEVEL1,
                        'category_parent' => LoadCategoryData::THIRD_LEVEL1,
                        'visibility' => null,
                        'account_group_visibility' => AccountGroupCategoryVisibility::PARENT_CATEGORY,
                        'account_visibility' => null,
                    ],
                    [
                        'category' => LoadCategoryData::FOURTH_LEVEL2,
                        'category_parent' => LoadCategoryData::THIRD_LEVEL2,
                        'visibility' => null,
                        'account_group_visibility' => AccountGroupCategoryVisibility::PARENT_CATEGORY,
                        'account_visibility' => AccountCategoryVisibility::HIDDEN,
                    ],
                ]
            ]
        ];
    }
}
