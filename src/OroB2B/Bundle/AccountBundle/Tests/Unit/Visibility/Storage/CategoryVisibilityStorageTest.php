<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Visibility\Storage;

use Doctrine\Common\Cache\CacheProvider;

use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Visibility\Calculator\CategoryVisibilityCalculator;
use OroB2B\Bundle\AccountBundle\Visibility\Storage\CategoryVisibilityData;
use OroB2B\Bundle\AccountBundle\Visibility\Storage\CategoryVisibilityStorage;

class CategoryVisibilityStorageTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var CategoryVisibilityStorage */
    protected $storage;

    /** @var CacheProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $cacheProvider;

    /** @var CategoryVisibilityCalculator|\PHPUnit_Framework_MockObject_MockObject */
    protected $calculator;

    protected function setUp()
    {
        $this->cacheProvider = $this->getMockBuilder('Doctrine\Common\Cache\CacheProvider')
            ->disableOriginalConstructor()
            ->setMethods(['deleteAll', 'delete', 'fetch', 'save'])
            ->getMockForAbstractClass();

        $this->calculator = $this->getMockBuilder(
            'OroB2B\Bundle\AccountBundle\Visibility\Calculator\CategoryVisibilityCalculator'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->storage = new CategoryVisibilityStorage($this->cacheProvider, $this->calculator);
    }

    protected function tearDown()
    {
        unset($this->cacheProvider, $this->calculator, $this->storage);
    }

    /**
     * @dataProvider getCategoryVisibilityDataDataProvider
     *
     * @param array|null $cacheValue
     * @param array|null $calculatedValue
     * @param CategoryVisibilityData $expectedValue
     */
    public function testGetCategoryVisibilityData($cacheValue, $calculatedValue, CategoryVisibilityData $expectedValue)
    {
        $this->cacheProvider->expects($this->once())
            ->method('fetch')
            ->with(CategoryVisibilityStorage::ALL_CACHE_ID)
            ->willReturn($cacheValue);

        if (!$cacheValue) {
            $this->calculator->expects($this->once())
                ->method('calculate')
                ->willReturn($calculatedValue);
        } else {
            $this->calculator->expects($this->never())
                ->method('calculate');
        }

        $this->assertCategoryVisibilityDataEquals($expectedValue, $this->storage->getCategoryVisibilityData());
    }

    /**
     * @return array
     */
    public function getCategoryVisibilityDataDataProvider()
    {
        return [
            'exist in cache' => [
                'cacheValue' => (new CategoryVisibilityData([1, 2, 3], [4, 5]))->toArray(),
                'calculatedValue' => null,
                'expectedValue' => new CategoryVisibilityData([1, 2, 3], [4, 5])
            ],
            'not exist in cache invisible' => [
                'cacheValue' => null,
                'calculatedValue' => new CategoryVisibilityData([1, 2, 3], [4, 5]),
                'expectedValue' => new CategoryVisibilityData([1, 2, 3], [4, 5])
            ]
        ];
    }

    /**
     * @dataProvider getCategoryVisibilityDataForAccountGroupDataProvider
     *
     * @param array|null $cacheValue
     * @param array|null $calculatedValue
     * @param CategoryVisibilityData $expectedValue
     */
    public function testGetCategoryVisibilityDataForAccountGroup(
        $cacheValue,
        $calculatedValue,
        CategoryVisibilityData $expectedValue
    ) {
        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountGroup', ['id' => 50]);

        $this->cacheProvider->expects($this->exactly(2))
            ->method('fetch')
            ->willReturnMap([
                [CategoryVisibilityStorage::ALL_CACHE_ID, $cacheValue['all']],
                [
                    CategoryVisibilityStorage::ACCOUNT_GROUP_CACHE_ID_PREFIX . '.' . $accountGroup->getId(),
                    $cacheValue['group'],
                ],
            ]);

        if ($calculatedValue['all']) {
            $this->calculator->expects($this->once())
                ->method('calculate')
                ->willReturn($calculatedValue['all']);
        } else {
            $this->calculator->expects($this->never())
                ->method('calculate');
        }
        if ($calculatedValue['group']) {
            $this->calculator->expects($this->once())
                ->method('calculateForAccountGroup')
                ->with($accountGroup)
                ->willReturn($calculatedValue['group']);
        } else {
            $this->calculator->expects($this->never())
                ->method('calculateForAccountGroup');
        }

        $this->assertCategoryVisibilityDataEquals(
            $expectedValue,
            $this->storage->getCategoryVisibilityDataForAccountGroup($accountGroup)
        );
    }

    /**
     * @return array
     */
    public function getCategoryVisibilityDataForAccountGroupDataProvider()
    {
        return [
            'exist in cache' => [
                'cacheValue' => [
                    'all' => (new CategoryVisibilityData([1, 2, 3], [4, 5]))->toArray(),
                    'group' => (new CategoryVisibilityData([4], [2]))->toArray(),
                ],
                'calculatedValue' => null,
                'expectedValue' => new CategoryVisibilityData([1, 3, 4], [2, 5])
            ],
            'not exist in cache' => [
                'cacheValue' => [
                    'all' => null,
                    'group' => null,
                ],
                'calculatedValue' => [
                    'all' => new CategoryVisibilityData([1, 2, 3], [4, 5]),
                    'group' => new CategoryVisibilityData([4], [2]),
                ],
                'expectedValue' => new CategoryVisibilityData([1, 3, 4], [2, 5])
            ],
            'particulary exist in cache' => [
                'cacheValue' => [
                    'all' => null,
                    'group' => (new CategoryVisibilityData([4], [2]))->toArray(),
                ],
                'calculatedValue' => [
                    'all' => new CategoryVisibilityData([1, 2, 3], [4, 5]),
                    'group' => null,
                ],
                'expectedValue' => new CategoryVisibilityData([1, 3, 4], [2, 5])
            ],
        ];
    }

    /**
     * @dataProvider getCategoryVisibilityDataForAccountDataProvider
     *
     * @param array|null $cacheValue
     * @param array|null $calculatedValue
     * @param CategoryVisibilityData $expectedValue
     */
    public function testGetCategoryVisibilityDataForAccount(
        $cacheValue,
        $calculatedValue,
        CategoryVisibilityData $expectedValue
    ) {
        /** @var AccountGroup */
        $accountGroup = $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountGroup', ['id' => 40]);

        /** @var Account $account */
        $account = $this->getEntity(
            'OroB2B\Bundle\AccountBundle\Entity\Account',
            ['id' => 50, 'group' => $accountGroup]
        );

        $this->cacheProvider->expects($this->exactly(3))
            ->method('fetch')
            ->willReturnMap([
                [CategoryVisibilityStorage::ALL_CACHE_ID, $cacheValue['all']],
                [
                    CategoryVisibilityStorage::ACCOUNT_GROUP_CACHE_ID_PREFIX . '.' . $accountGroup->getId(),
                    $cacheValue['group'],
                ],
                [CategoryVisibilityStorage::ACCOUNT_CACHE_ID_PREFIX . '.' . $account->getId(), $cacheValue['account']],
            ]);

        if ($calculatedValue['all']) {
            $this->calculator->expects($this->once())
                ->method('calculate')
                ->willReturn($calculatedValue['all']);
        } else {
            $this->calculator->expects($this->never())
                ->method('calculate');
        }
        if ($calculatedValue['group']) {
            $this->calculator->expects($this->once())
                ->method('calculateForAccountGroup')
                ->with($accountGroup)
                ->willReturn($calculatedValue['group']);
        } else {
            $this->calculator->expects($this->never())
                ->method('calculateForAccountGroup');
        }
        if ($calculatedValue['account']) {
            $this->calculator->expects($this->once())
                ->method('calculateForAccount')
                ->with($account)
                ->willReturn($calculatedValue['account']);
        } else {
            $this->calculator->expects($this->never())
                ->method('calculateForAccount');
        }
        $this->assertCategoryVisibilityDataEquals(
            $expectedValue,
            $this->storage->getCategoryVisibilityDataForAccount($account)
        );
    }

    /**
     * @return array
     */
    public function getCategoryVisibilityDataForAccountDataProvider()
    {
        return [
            'exist in cache' => [
                'cacheValue' => [
                    'all' => (new CategoryVisibilityData([1, 2, 3, 4], [5, 6, 7, 8]))->toArray(),
                    'group' => (new CategoryVisibilityData([5, 6], [1, 2]))->toArray(),
                    'account' => (new CategoryVisibilityData([1, 7], [3, 5]))->toArray(),
                ],
                'calculatedValue' => null,
                'expectedValue' => new CategoryVisibilityData([1, 4, 6, 7], [2, 3, 5, 8])
            ],
            'not exist in cache' => [
                'cacheValue' => [
                    'all' => null,
                    'group' => null,
                    'account' => null,
                ],
                'calculatedValue' => [
                    'all' => new CategoryVisibilityData([1, 2, 3, 4], [5, 6, 7, 8]),
                    'group' => new CategoryVisibilityData([5, 6], [1, 2]),
                    'account' => new CategoryVisibilityData([1, 7], [3, 5]),
                ],
                'expectedValue' => new CategoryVisibilityData([1, 4, 6, 7], [2, 3, 5, 8])
            ],
            'particulary exist in cache' => [
                'cacheValue' => [
                    'all' => null,
                    'group' => (new CategoryVisibilityData([5, 6], [1, 2]))->toArray(),
                    'account' => null,
                ],
                'calculatedValue' => [
                    'all' => new CategoryVisibilityData([1, 2, 3, 4], [5, 6, 7, 8]),
                    'group' => null,
                    'account' => new CategoryVisibilityData([1, 7], [3, 5]),
                ],
                'expectedValue' => new CategoryVisibilityData([1, 4, 6, 7], [2, 3, 5, 8])
            ],
        ];
    }

    public function testFlush()
    {
        $this->cacheProvider->expects($this->once())
            ->method('deleteAll');

        $this->storage->flush();
    }

    public function testClear()
    {
        $this->cacheProvider->expects($this->any())
            ->method('delete')
            ->with(CategoryVisibilityStorage::ALL_CACHE_ID);

        $this->storage->clear();
    }

    public function testClearForAccountGroup()
    {
        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountGroup', ['id' => 40]);

        $this->cacheProvider->expects($this->any())
            ->method('delete')
            ->with(CategoryVisibilityStorage::ACCOUNT_GROUP_CACHE_ID_PREFIX . '.' . $accountGroup->getId());

        $this->storage->clearForAccountGroup($accountGroup);
    }

    public function testClearForAccount()
    {
        /** @var Account $account */
        $account = $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', ['id' => 40]);

        $this->cacheProvider->expects($this->any())
            ->method('delete')
            ->with(CategoryVisibilityStorage::ACCOUNT_CACHE_ID_PREFIX . '.' . $account->getId());

        $this->storage->clearForAccount($account);
    }

    /**
     * @param CategoryVisibilityData $expected
     * @param CategoryVisibilityData $actual
     */
    protected function assertCategoryVisibilityDataEquals(
        CategoryVisibilityData $expected,
        CategoryVisibilityData $actual
    ) {
        $this->assertCount(count($expected->getVisibleCategoryIds()), $actual->getVisibleCategoryIds());

        foreach ($expected->getVisibleCategoryIds() as $categoryId) {
            $this->assertContains($categoryId, $actual->getVisibleCategoryIds());
            $this->assertNotContains($categoryId, $actual->getHiddenCategoryIds());
            $this->assertTrue($actual->isCategoryVisible($categoryId));
        }

        $this->assertCount(count($expected->getHiddenCategoryIds()), $actual->getHiddenCategoryIds());
        foreach ($expected->getHiddenCategoryIds() as $categoryId) {
            $this->assertContains($categoryId, $actual->getHiddenCategoryIds());
            $this->assertNotContains($categoryId, $actual->getVisibleCategoryIds());
            $this->assertFalse($actual->isCategoryVisible($categoryId));
        }
    }
}
