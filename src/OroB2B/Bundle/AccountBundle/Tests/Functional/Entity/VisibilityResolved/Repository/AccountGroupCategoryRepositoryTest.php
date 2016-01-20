<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\VisibilityResolved\Repository;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountGroupCategoryVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\AccountGroupCategoryRepository;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

/**
 * @dbIsolation
 */
class AccountGroupCategoryRepositoryTest extends AbstractCategoryRepositoryTest
{
    /**
     * @var AccountGroupCategoryRepository
     */
    protected $repository;

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

        $actualVisibility = $this->repository->isCategoryVisible($category, $accountGroup, $configValue);

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

        $categoryIds = $this->repository->getCategoryIdsByVisibility($visibility, $accountGroup, $configValue);

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

    public function testClearTable()
    {
        $this->assertGreaterThan(0, $this->getEntitiesCount());
        $this->repository->clearTable();
        $this->assertEquals(0, $this->getEntitiesCount());
    }

    public function testInsertStaticValues()
    {
        /** @var AccountGroupCategoryVisibility[] $visibilities */
        $visibilities = $this->getManagerRegistry()
            ->getManagerForClass('OroB2BAccountBundle:Visibility\AccountGroupCategoryVisibility')
            ->getRepository('OroB2BAccountBundle:Visibility\AccountGroupCategoryVisibility')
            ->createQueryBuilder('entity')
            ->andWhere('entity.visibility IN (:scalarVisibilities)')
            ->setParameter(
                'scalarVisibilities',
                [AccountGroupCategoryVisibility::VISIBLE, AccountGroupCategoryVisibility::HIDDEN]
            )
            ->getQuery()
            ->getResult();
        $this->assertNotEmpty($visibilities);

        /** @var AccountGroupCategoryVisibility[] $indexedVisibilities */
        $indexedVisibilities = [];
        foreach ($visibilities as $visibility) {
            $indexedVisibilities[$visibility->getId()] = $visibility;
        }

        $this->repository->clearTable();
        $this->repository->insertStaticValues($this->getInsertExecutor());

        $resolvedVisibilities = $this->getResolvedVisibilities();

        $this->assertSameSize($indexedVisibilities, $resolvedVisibilities);
        foreach ($resolvedVisibilities as $resolvedVisibility) {
            $id = $resolvedVisibility['sourceCategoryVisibility'];
            $this->assertArrayHasKey($id, $indexedVisibilities);
            $visibility = $indexedVisibilities[$id];

            $this->assertEquals($visibility->getCategory()->getId(), $resolvedVisibility['category']);
            $this->assertEquals($visibility->getAccountGroup()->getId(), $resolvedVisibility['accountGroup']);
            $this->assertEquals(AccountGroupCategoryVisibilityResolved::SOURCE_STATIC, $resolvedVisibility['source']);
            if ($visibility->getVisibility() === AccountGroupCategoryVisibility::VISIBLE) {
                $this->assertEquals(
                    AccountGroupCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                    $resolvedVisibility['visibility']
                );
            } else {
                $this->assertEquals(
                    AccountGroupCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                    $resolvedVisibility['visibility']
                );
            }
        }
    }

    public function testInsertParentCategoryValues()
    {

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
     * @return array
     */
    protected function getResolvedVisibilities()
    {
        return $this->repository->createQueryBuilder('entity')
            ->select(
                'IDENTITY(entity.sourceCategoryVisibility) as sourceCategoryVisibility',
                'IDENTITY(entity.category) as category',
                'IDENTITY(entity.accountGroup) as accountGroup',
                'entity.visibility',
                'entity.source'
            )
            ->getQuery()
            ->getArrayResult();
    }
}
