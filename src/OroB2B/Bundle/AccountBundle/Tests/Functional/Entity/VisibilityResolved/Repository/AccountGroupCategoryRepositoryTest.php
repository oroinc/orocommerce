<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\VisibilityResolved\Repository;

use Oro\Component\Testing\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\CategoryVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\AccountGroupCategoryRepository;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

/**
 * @dbIsolation
 */
class AccountGroupCategoryRepositoryTest extends WebTestCase
{
    const ROOT_CATEGORY = 'root';

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
     * @param string $accountGroupName
     * @param int $configValue
     * @param bool $expectedVisibility
     */
    public function testIsCategoryVisible($categoryName, $accountGroupName, $configValue, $expectedVisibility)
    {
        /** @var Category $category */
        $category = $this->getReference($categoryName);

        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->getReference($accountGroupName);

        $actualVisibility = $this->getRepository()->isCategoryVisible($category, $accountGroup, $configValue);

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
                'accountGroupName' => 'account_group.group1',
                'configValue' => 1,
                'expectedVisibility' => true,
            ],
            [
                'categoryName' => 'category_1',
                'accountGroupName' => 'account_group.group3',
                'configValue' => 1,
                'expectedVisibility' => true,
            ],
            [
                'categoryName' => 'category_1_2',
                'accountGroupName' => 'account_group.group1',
                'configValue' => 1,
                'expectedVisibility' => true,
            ],
            [
                'categoryName' => 'category_1_2',
                'accountGroupName' => 'account_group.group3',
                'configValue' => 1,
                'expectedVisibility' => true,
            ],
            [
                'categoryName' => 'category_1_2_3',
                'accountGroupName' => 'account_group.group1',
                'configValue' => 1,
                'expectedVisibility' => true,
            ],
            [
                'categoryName' => 'category_1_2_3',
                'accountGroupName' => 'account_group.group3',
                'configValue' => 1,
                'expectedVisibility' => false,
            ],
        ];
    }

    /**
     * @dataProvider getCategoryIdsByVisibilityDataProvider
     * @param int $visibility
     * @param string $accountGroupName
     * @param int $configValue
     * @param array $expected
     */
    public function testGetCategoryIdsByVisibility($visibility, $accountGroupName, $configValue, array $expected)
    {
        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->getReference($accountGroupName);

        $categoryIds = $this->getRepository()->getCategoryIdsByVisibility($visibility, $accountGroup, $configValue);

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
                'accountGroupName' => 'account_group.group1',
                'configValue' => 1,
                'expected' => [
                    'category_1',
                    'category_1_2',
                    'category_1_2_3',
                    'category_1_2_3_4',
                    'category_1_5',
                    'category_1_5_6',
                    'category_1_5_6_7',
                ]
            ],
            [
                'visibility' => BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                'accountGroupName' => 'account_group.group1',
                'configValue' => 1,
                'expected' => []
            ],
            [
                'visibility' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'accountGroupName' => 'account_group.group2',
                'configValue' => 1,
                'expected' => [
                    'category_1',
                    'category_1_2',
                    'category_1_2_3',
                    'category_1_2_3_4',
                    'category_1_5',
                    'category_1_5_6',
                ]
            ],
            [
                'visibility' => BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                'accountGroupName' => 'account_group.group2',
                'configValue' => 1,
                'expected' => [
                    'category_1_5_6_7',
                ]
            ],
            [
                'visibility' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'accountGroupName' => 'account_group.group3',
                'configValue' => 1,
                'expected' => [
                    'category_1',
                    'category_1_2',
                ]
            ],
            [
                'visibility' => BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                'accountGroupName' => 'account_group.group3',
                'configValue' => 1,
                'expected' => [
                    'category_1_2_3',
                    'category_1_2_3_4',
                    'category_1_5',
                    'category_1_5_6',
                    'category_1_5_6_7',
                ]
            ],
        ];
    }


    /**
     * @param array $expectedVisibilities
     * @dataProvider getParentCategoryVisibilitiesDataProvider
     */
    public function testGetParentCategoryVisibilities(array $expectedVisibilities)
    {
        $expectedVisibilities = $this->convertReferences($expectedVisibilities);
        $actualVisibilities = $this->getRepository()->getParentCategoryVisibilities();

        $this->assertSameSize($expectedVisibilities, $actualVisibilities);
        foreach ($actualVisibilities as $actualVisibility) {
            $this->assertContains($actualVisibility, $expectedVisibilities);
        }
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
                    'parent_category_resolved_visibility' => null,
                ],
                [
                    'visibility_id' => 'category_1_2.visibility.account_group.group1',
                    'parent_visibility_id' => 'category_1.visibility.account_group.group1',
                    'parent_visibility' => AccountGroupCategoryVisibility::HIDDEN,
                    'category_id' => 'category_1_2',
                    'parent_category_id' => 'category_1',
                    'parent_category_resolved_visibility' => CategoryVisibilityResolved::VISIBILITY_VISIBLE,
                ],
                [
                    'visibility_id' => 'category_1_2.visibility.account_group.group2',
                    'parent_visibility_id' => null,
                    'parent_visibility' => null,
                    'category_id' => 'category_1_2',
                    'parent_category_id' => 'category_1',
                    'parent_category_resolved_visibility' => CategoryVisibilityResolved::VISIBILITY_VISIBLE,
                ],
                [
                    'visibility_id' => 'category_1_5.visibility.account_group.group3',
                    'parent_visibility_id' => 'category_1.visibility.account_group.group3',
                    'parent_visibility' => AccountGroupCategoryVisibility::PARENT_CATEGORY,
                    'category_id' => 'category_1_5',
                    'parent_category_id' => 'category_1',
                    'parent_category_resolved_visibility' => CategoryVisibilityResolved::VISIBILITY_VISIBLE,
                ],
                [
                    'visibility_id' => 'category_1_2_3.visibility.account_group.group1',
                    'parent_visibility_id' => 'category_1_2.visibility.account_group.group1',
                    'parent_visibility' => AccountGroupCategoryVisibility::PARENT_CATEGORY,
                    'category_id' => 'category_1_2_3',
                    'parent_category_id' => 'category_1_2',
                    'parent_category_resolved_visibility' => CategoryVisibilityResolved::VISIBILITY_VISIBLE,
                ],
                [
                    'visibility_id' => 'category_1_5_6.visibility.account_group.group1',
                    'parent_visibility_id' => null,
                    'parent_visibility' => null,
                    'category_id' => 'category_1_5_6',
                    'parent_category_id' => 'category_1_5',
                    'parent_category_resolved_visibility' => CategoryVisibilityResolved::VISIBILITY_VISIBLE,
                ],
                [
                    'visibility_id' => 'category_1_5_6.visibility.account_group.group3',
                    'parent_visibility_id' => 'category_1_5.visibility.account_group.group3',
                    'parent_visibility' => AccountGroupCategoryVisibility::PARENT_CATEGORY,
                    'category_id' => 'category_1_5_6',
                    'parent_category_id' => 'category_1_5',
                    'parent_category_resolved_visibility' => CategoryVisibilityResolved::VISIBILITY_VISIBLE,
                ],
                [
                    'visibility_id' => 'category_1_2_3_4.visibility.account_group.group3',
                    'parent_visibility_id' => 'category_1_2_3.visibility.account_group.group3',
                    'parent_visibility' => AccountGroupCategoryVisibility::HIDDEN,
                    'category_id' => 'category_1_2_3_4',
                    'parent_category_id' => 'category_1_2_3',
                    'parent_category_resolved_visibility' => CategoryVisibilityResolved::VISIBILITY_VISIBLE,
                ],
                [
                    'visibility_id' => 'category_1_2_3_4.visibility.account_group.group1',
                    'parent_visibility_id' => 'category_1_2_3.visibility.account_group.group1',
                    'parent_visibility' => AccountGroupCategoryVisibility::PARENT_CATEGORY,
                    'category_id' => 'category_1_2_3_4',
                    'parent_category_id' => 'category_1_2_3',
                    'parent_category_resolved_visibility' => CategoryVisibilityResolved::VISIBILITY_VISIBLE,
                ],
                [
                    'visibility_id' => 'category_1_5_6_7.visibility.account_group.group1',
                    'parent_visibility_id' => 'category_1_5_6.visibility.account_group.group1',
                    'parent_visibility' => AccountGroupCategoryVisibility::PARENT_CATEGORY,
                    'category_id' => 'category_1_5_6_7',
                    'parent_category_id' => 'category_1_5_6',
                    'parent_category_resolved_visibility' => CategoryVisibilityResolved::VISIBILITY_HIDDEN,
                ],
                [
                    'visibility_id' => 'category_1_5_6_7.visibility.account_group.group3',
                    'parent_visibility_id' => 'category_1_5_6.visibility.account_group.group3',
                    'parent_visibility' => AccountGroupCategoryVisibility::PARENT_CATEGORY,
                    'category_id' => 'category_1_5_6_7',
                    'parent_category_id' => 'category_1_5_6',
                    'parent_category_resolved_visibility' => CategoryVisibilityResolved::VISIBILITY_HIDDEN,
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

    /**
     * @param string $reference
     * @return integer
     */
    protected function getCategoryId($reference)
    {
        if ($reference === self::ROOT_CATEGORY) {
            return $this->getContainer()->get('doctrine')
                ->getRepository('OroB2BCatalogBundle:Category')
                ->getMasterCatalogRoot()
                ->getId();
        }

        return $reference ? $this->getReference($reference)->getId() : null;
    }

    /**
     * @return AccountGroupCategoryRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved')
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved');
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
